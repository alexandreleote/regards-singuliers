<?php

namespace App\Controller;

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

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'register')]
    public function register(
        Request $request, 
        UserAuthenticatorInterface $userAuthenticator, 
        #[Autowire(service: 'security.authenticator.form_login.main')]
        AuthenticatorInterface $authenticator,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        // If the user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
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
            } else {
                // If form is submitted but not valid, we need to handle Turbo by redirecting
                // This is important for Turbo to work properly with form errors
                $this->addFlash('error', 'Il y a des erreurs dans le formulaire. Veuillez vérifier vos informations.');
                
                // Redirect back to the registration page to show errors
                return $this->redirectToRoute('register');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'page_title' => 'Inscription - regards singuliers'
        ]);
    }

    #[Route('/verify/email', name: 'verify_email')]
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

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Votre adresse email a bien été vérifiée.');

        return $this->redirectToRoute('home');
    }
}
