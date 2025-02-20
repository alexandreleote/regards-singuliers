<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private MailerInterface $mailer
    ) {}

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Set default values
                $user->setIsBanned(false);
                $user->setIsTempBanned(false);
                $user->setRoles(['ROLE_USER']);

                try {
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->logger->info('Utilisateur créé avec succès, tentative d\'envoi d\'email...', [
                        'email' => $user->getEmail(),
                        'id' => $user->getId()
                    ]);

                    // Générer l'URL signée
                    $signatureComponents = $this->emailVerifier->getVerifyEmailHelper()->generateSignature(
                        'app_verify_email',
                        $user->getId(),
                        $user->getEmail(),
                        ['id' => $user->getId()]
                    );

                    // Créer et envoyer l'email de vérification
                    $email = (new TemplatedEmail())
                        ->from(new Address('no-reply@regards-singuliers.fr', 'Regards Singuliers'))
                        ->to($user->getEmail())
                        ->subject('Confirmez votre adresse email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                        ->context([
                            'signedUrl' => $signatureComponents->getSignedUrl(),
                            'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                            'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
                        ]);

                    $this->mailer->send($email);
                    $this->logger->info('Email de vérification envoyé avec succès');

                    $this->addFlash('success', 'Votre compte a été créé avec succès. Un email de confirmation vous a été envoyé.');
                    return $this->redirectToRoute('app_home');
                } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                    $this->logger->error('Tentative d\'inscription avec un email existant : ' . $user->getEmail());
                    $this->addFlash('error', 'Un compte existe déjà avec cette adresse email. Veuillez vous connecter ou utiliser une autre adresse.');
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de l\'inscription : ' . $e->getMessage(), [
                        'exception' => $e,
                        'email' => $user->getEmail()
                    ]);
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du compte: ' . $e->getMessage());
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        try {
            // Récupérer l'ID de l'utilisateur depuis la requête
            $id = $request->query->get('id');
            if (!$id) {
                throw new \InvalidArgumentException('ID utilisateur manquant');
            }

            $user = $this->entityManager->getRepository(User::class)->find($id);
            if (!$user) {
                throw new \InvalidArgumentException('Utilisateur non trouvé');
            }

            $this->emailVerifier->handleEmailConfirmation($request, $user);

            $user->setIsVerified(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre compte a été vérifié avec succès.');

            // Rediriger vers la page de connexion
            return $this->redirectToRoute('app_login');
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());
            return $this->redirectToRoute('app_register');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la vérification de votre email.');
            return $this->redirectToRoute('app_register');
        }
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
