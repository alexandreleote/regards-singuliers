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
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class SecurityController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

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
    public function forgottenPassword(
        Request $request, 
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            // Vérification du token CSRF
            if (!$this->isCsrfTokenValid('reset_password_request', $request->request->get('_csrf_token'))) {
                $this->addFlash('danger', 'Le token de sécurité est invalide.');
                return $this->redirectToRoute('login');
            }

            $user = $userRepository->findOneByEmail($form->get('email')->getData());

            if($user) {
                // Génération d'un token unique
                $token = bin2hex(random_bytes(32));
                $expiresAt = new \DateTimeImmutable('+1 hour');

                $user->setResetToken($token);
                $user->setResetTokenExpiresAt($expiresAt);

                $entityManager->flush();

                // On génère l'URL vers reset_password
                $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                $email = (new TemplatedEmail())
                    ->from('no-reply@regards-singuliers.com')
                    ->to($user->getEmail())
                    ->subject('Récupération de mot de passe sur le site regards singuliers')
                    ->htmlTemplate('email/password_reset.html.twig')
                    ->context([
                        'user' => $user,
                        'url' => $url
                    ]);

                $this->emailVerifier->sendEmailConfirmation('reset_password', $user, $email);

                $this->addFlash('success', 'Email envoyé avec succès');
                return $this->redirectToRoute('login');
            }

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
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager 
    ): Response
    {
        $user = $userRepository->findOneByResetToken($token);

        if($user && $user->isResetTokenValid()){
            $form = $this->createForm(ResetPasswordFormType::class);

            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                // Vérification du token CSRF
                if (!$this->isCsrfTokenValid('reset_password', $request->request->get('_csrf_token'))) {
                    $this->addFlash('danger', 'Le token de sécurité est invalide.');
                    return $this->redirectToRoute('login');
                }

                $user->setPassword(
                    $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                );

                // Réinitialisation du token
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);

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

        $this->addFlash('danger', 'Le token est invalide ou a expiré');
        return $this->redirectToRoute('login');
    }
}
