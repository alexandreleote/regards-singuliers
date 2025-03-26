<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/connexion', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'page_title' => 'Connexion - regards singuliers',
            'meta_description' => 'Suivez l\'avancement de votre projet, consultez nos échanges et retrouvez tous vos documents personnalisés. Votre espace privé dédié à la conception de votre intérieur.',
            'error' => $error,
        ]);
    }

    #[Route(path: '/deconnexion', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/mot-de-passe-oublie', name: 'forgotten_password')]
    public function forgottenPassword(Request $request, UserRepository $userRepository, JWTService $jwt, SendEmailService $mail): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            // Le formulaire est envoyé ET valide
            // On cherche l'utilisateur en base de données

            // On récupère l'adresse mail de l'utilisateur depuis le formulaire pour vérifier en base de données
            $user = $userRepository->findOneByEmail($form->get('email')->getData());

            // Vérification de l'existence de l'utilisateur
            if($user) {
                // On génère un Token

                $header = [
                    'typ' => 'JWT',
                    'alg' => 'HS256'
                ];
                
                $payload = [
                    'user_id' => $user->getId()
                ];

                $token = $jwt->generate($header, $payload,$this->getParameter('app.jwtsecret'));

                // On génère l'URL vers reset_password
                $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL); // On veut avoir le chemin complet qui est généré dans notre mail de reset

                $mail->send('no-reply@regards-singuliers.com',
                $user->getEmail(),
                'Récupération de mot de passe sur le site regards singuliers',
                'password_reset',
                compact('user', 'url') // ['user' => $user, 'url' => $url]
                );

                $this->addFlash('success', 'Email envoyé avec succès');
                return $this->redirectToRoute('login');
            }

            // Si l'utilisateur n'existe pas
            $this->addFlash('danger', 'Un problème est survenu');
            return $this->redirectToRoute('login');

        }
        
        return $this->render('security/reset_password_request.html.twig',  [
            'requestPassForm' => $form->createView(),
            'page_title' => 'Demande de réinitialisation de mot de passe',
            'meta_description' => 'Formulaire permettant de réinitiliaser le mot de passe.'
        ]);
    }

    #[Route(path: '/mot-de-passe-oublie/{token}', name: 'reset_password')]
    public function resetPassword(
        $token, 
        UserRepository $userRepository, 
        JWTService $jwt, 
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager 
    ): Response
    {
        if($jwt->isValid($token) && ! $jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){
            $payload = $jwt->getPayload($token);

            $user = $userRepository->find($payload['user_id']);
        
            if($user){
                $form = $this->createForm(ResetPasswordFormType::class);

                $form->handleRequest($request);

                if($form->isSubmitted() && $form->isValid()){
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $form->get('password')->getData())
                    );

                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Mot de passe changé avec succès');
                    return $this->redirectToRoute('login');
                }

                return $this->render('security/reset_password.html.twig', [
                    'passForm' => $form->createView(),
                    'meta_description' => '',
                    'page_title' => 'Récupération de votre mot de passe'
                ]);
            }
        }

        $this->addFlash('danger', 'le token est invalide ou a expiré');
        return $this->redirectToRoute('login');
    }
}
