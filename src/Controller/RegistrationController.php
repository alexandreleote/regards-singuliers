<?php

namespace App\Controller;

use App\Entity\BotIp;
use App\Entity\User;
use App\Security\EmailVerifier;
use App\Form\RegistrationFormType;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/inscription', name: 'register')]
    public function register(
        Request $request, 
        UserAuthenticatorInterface $userAuthenticator, 
        #[Autowire(service: 'security.authenticator.form_login.main')]
        AuthenticatorInterface $authenticator,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Si l'utilisateur est déjà connecté, il est redirigé vers la home page
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        // Vérifier si les champs honeypot sont remplis (détection de bot)
        if ($request->isMethod('POST') && 
            ($request->request->has('phone') && !empty($request->request->get('phone')) || 
             $request->request->has('work_email') && !empty($request->request->get('work_email')))) {
            
            // Enregistrer l'IP du bot
            $botIp = new BotIp();
            $botIp->setIp($request->getClientIp());
            $botIp->setUserAgent($request->headers->get('User-Agent'));
            $botIp->setFormType('registration_form');
            
            $entityManager->persist($botIp);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Inscription non autorisée'
                ], Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form,
                'page_title' => 'Inscription - regards singuliers',
                'meta_description' => 'Inscription - regards singuliers',
            ]);
        }
        
        // Traitement normal du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérifier d'abord si l'email existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $form->get('email')->getData()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez vous connecter ou utiliser une autre adresse email.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'page_title' => 'Inscription - regards singuliers',
                    'meta_description' => 'Créez votre compte client et simplifiez le suivi de votre projet de décoration. Accédez à l\'espace personnel qui vous permet de communiquer directement avec notre architecte.',
                ]);
            }
            
            // Vérifier si l'IP est bannie
            $errors = $form->getConfig()->getType()->getInnerType()->validateRegistration($user);
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'page_title' => 'Inscription - regards singuliers',
                    'meta_description' => 'Inscription - regards singuliers',
                ]);
            }
            
            if ($form->isValid()) {
                try {
                    /** @var string $plainPassword */
                    $plainPassword = $form->get('plainPassword')->getData();

                    // Add the creation date of the account
                    $user->setCreatedAt(new \DateTimeImmutable());

                    // Set the user role
                    $user->setRoles(['ROLE_USER']);

                    // encode the plain password
                    $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

                    $entityManager->persist($user);
                    $entityManager->flush();

                    // generate a signed url and email it to the user
                    $this->emailVerifier->sendEmailConfirmation('verify_email', $user,
                        (new TemplatedEmail())
                            ->from(new Address('no-reply@regards-singuliers.com', 'no-reply'))
                            ->to((string) $user->getEmail())
                            ->subject('Veuillez confirmer votre Email')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                    );
                    
                    $this->addFlash('success', 'Inscription réussie! Veuillez vérifier votre email et cliquer sur le lien de vérification.');
                    
                    // Authenticate user and redirect
                    return $userAuthenticator->authenticateUser($user, $authenticator, $request);
                } catch (UniqueConstraintViolationException $e) {
                    $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez vous connecter ou utiliser une autre adresse email.');
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                        'page_title' => 'Inscription - regards singuliers',
                        'meta_description' => 'Inscription - regards singuliers',
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.');
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                        'page_title' => 'Inscription - regards singuliers',
                        'meta_description' => 'Inscription - regards singuliers',
                    ]);
                }
            } else {
                // Check if there are password errors
                if ($form->get('plainPassword')->getErrors(true)->count() > 0) {
                    $passwordErrorMessage = "Votre mot de passe doit contenir au minimum :<br>
                        - 12 caractères<br>
                        - Une majuscule<br>
                        - Une minuscule<br>
                        - Un chiffre<br>
                        - Un caractère spécial (@$!%*?&)";
                    
                    $this->addFlash('password_error', $passwordErrorMessage);
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'page_title' => 'Inscription - regards singuliers',
            'meta_description' => 'Inscription - regards singuliers',
        ]);
    }

    #[Route('/verification/email', name: 'verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('register');
        }

        $this->addFlash('success', 'Votre adresse email a bien été vérifiée.');

        return $this->redirectToRoute('home');
    }
}
