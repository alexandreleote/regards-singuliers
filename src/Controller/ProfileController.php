<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Payment;
use App\Entity\Reservation;
use Psr\Log\LoggerInterface;
use App\Form\ProfileEditType;
use App\Service\StripeService;
use App\Service\CalendlyService;
use App\Form\DeleteAccountFormType;
use App\Service\AnonymizationService;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('', name: 'profile')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        $reservations = $reservationRepository->findByUser($user);
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
            'page_title' => 'Mon Profil - regards singuliers',
            'meta_description' => 'Accédez à votre profil sur regards singuliers pour gérer vos informations personnelles, suivre l\'avancement de vos projets et interagir avec notre équipe.',
        ]);
    }

    #[Route('/modification', name: 'profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du mot de passe
            if (!empty($form->get('currentPassword')->getData())) {
                if (!$passwordHasher->isPasswordValid($user, $form->get('currentPassword')->getData())) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->render('profile/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                        'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
                        'page_title' => 'Modifier mon profil - '.$user->getFullName()
                    ]);
                }

                if ($form->get('newPassword')->getData() !== $form->get('confirmPassword')->getData()) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->render('profile/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                        'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
                        'page_title' => 'Modifier mon profil - '.$user->getFullName()
                    ]);
                }

                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('newPassword')->getData()
                    )
                );
            }

            // Validation de l'entité
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('profile/edit.html.twig', [
                    'form' => $form->createView(),
                    'user' => $user,
                    'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
                    'page_title' => 'Modifier mon profil - '.$user->getFullName()
                ]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
            'page_title' => 'Modifier mon profil - '.$user->getFullName()
        ]);
    }

    #[Route('/reservations/{filter}', name: 'profile_reservations', defaults: ['filter' => 'aujourd-hui'])]
    public function reservations(ReservationRepository $reservationRepository, string $filter = 'aujourd-hui'): Response
    {
        // Vérifier que le filtre est valide
        if (!in_array($filter, ['passees', 'aujourd-hui', 'a-venir'])) {
            $filter = 'aujourd-hui';
        }
        
        $user = $this->getUser();
        $reservations = $reservationRepository->findByUserAndAppointmentDate($user, $filter);
        
        return $this->render('profile/reservations.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
            'filter' => $filter,
            'page_title' => 'Mes réservations - regards singuliers',
            'meta_description' => 'Consultez et gérez vos réservations sur regards singuliers, suivez l\'avancement de vos projets et planifiez vos prochaines étapes avec notre équipe.',
        ]);
    }

    #[Route('/reservation/{id}/cancel', name: 'reservation_cancel')]
    public function cancelReservation(
        Request $request,
        Reservation $reservation,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        StripeService $stripeService
    ): Response {
        if (!$this->isCsrfTokenValid('cancel-reservation-' . $reservation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $now = new \DateTime();
        $appointmentTime = $reservation->getAppointmentDatetime();
        $timeBeforeAppointment = $appointmentTime->getTimestamp() - $now->getTimestamp();
        $hoursBeforeAppointment = $timeBeforeAppointment / 3600;

        $payment = $reservation->getPayments()->first();
        if (!$payment) {
            throw $this->createNotFoundException('No payment found for this reservation');
        }

        // If cancellation is more than 72 hours before appointment, process refund
        if ($hoursBeforeAppointment > 72) {
            $stripeService->refundPayment(
                $payment->getStripePaymentId(),
                floatval($payment->getDepositAmount())
            );
        }

        $reservation->setStatus('cancelled');
        $entityManager->flush();

        return $this->render('profile/reservation_cancel.html.twig', [
            'refunded' => $hoursBeforeAppointment > 72,
            'amount' => $payment->getDepositAmount(),
            'page_title' => 'Annulation confirmée - regards singuliers'
        ]);
    }

    #[Route('/reservation/{id}', name: 'profile_reservation_detail')]
    public function reservationDetail(Reservation $reservation): Response
    {
        return $this->render('profile/reservation_detail.html.twig', [
            'reservation' => $reservation,
            'page_title' => 'Détail de la réservation - regards singuliers'
        ]);
    }


    /* #[Route('/reservation/{id}/cancel', name: 'reservation_cancel', methods: ['POST'])]
    public function cancelReservation(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
    ): Response {
        $reservation = $reservationRepository->find($id);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }
        
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cancel-reservation-'.$reservation->getId(), $token)) {
            throw new InvalidCsrfTokenException('Token CSRF invalide');
        }
        
        // Vérifier si le remboursement est possible (> 72h)
        $now = new \DateTime();
        $appointmentTime = $reservation->getAppointmentDatetime();
        $interval = $now->diff($appointmentTime);
        $hoursBeforeAppointment = ($interval->days * 24) + $interval->h;
        
        if ($hoursBeforeAppointment > 72) {
            // Remboursement via Stripe
            try {
                $stripeService->refundPayment($reservation->getStripePaymentIntentId());
                $reservation->setStatus('remboursé');
                $this->addFlash('success', 'Votre réservation a été annulée et sera remboursée.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du remboursement. Veuillez nous contacter.');
                return $this->redirectToRoute('reservation_detail', ['id' => $id]);
            }
        } else {
            $reservation->setStatus('annulé');
            $this->addFlash('success', 'Votre réservation a été annulée sans remboursement.');
        }
        
        $entityManager->flush();
        
        return $this->redirectToRoute('profile_reservations');
    } */

    /* #[Route('/annulation/{id}', name: 'profile_reservation_detail')]
    public function cancelReservation(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        CalendlyService $calendlyService,
        StripeService $stripeService,
        LoggerInterface $logger
    ): Response {
        $reservation = $reservationRepository->find($id);
        
        if (!$reservation) {
            $this->addFlash('error', 'Réservation non trouvée.');
            return $this->redirectToRoute('profile_reservations');
        }
        
        // Vérification de l'accès
        if ($reservation->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }

        // Vérification si la réservation peut être annulée
        if (!in_array($reservation->getStatus(), ['en attente', 'confirmé'])) {
            $this->addFlash('error', 'Cette réservation ne peut plus être annulée.');
            return $this->redirectToRoute('profile_reservations');
        }

        // Vérification du remboursement (72h)
        $canBeRefunded = $this->canRefundReservation($reservation);
        
        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('cancel-reservation-'.$reservation->getId(), $token)) {
                throw new InvalidCsrfTokenException('Token CSRF invalide');
            }
            
            try {
                // Annulation Calendly
                if ($reservation->getCalendlyEventId()) {
                    $eventId = str_replace('https://api.calendly.com/scheduled_events/', '', $reservation->getCalendlyEventId());
                    $calendlyService->cancelEvent($eventId);
                }
                
                // Remboursement si possible
                if ($canBeRefunded && $reservation->getStripePaymentIntentId()) {
                    try {
                        $stripeService->refundPayment(
                            $reservation->getStripePaymentIntentId(),
                            $reservation->getPaymentAmount() // Montant réel payé par l'utilisateur (50%)
                        );
                        $reservation->setStatus('remboursé');
                        $this->addFlash('success', 'Votre réservation a été annulée et sera remboursée.');
                    } catch (\Exception $e) {
                        $logger->error('Erreur lors du remboursement Stripe: ' . $e->getMessage());
                        throw new \Exception('Erreur lors du remboursement. Veuillez contacter le support.');
                    }
                } else {
                    $reservation->setStatus('annulé');
                    $this->addFlash('success', 'Votre réservation a été annulée sans remboursement.');
                }
                
                $entityManager->flush();
                return $this->redirectToRoute('profile_reservations');
                
            } catch (\Exception $e) {
                $logger->error('Erreur lors de l\'annulation: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'annulation: ' . $e->getMessage());
            }
        }
        
        return $this->render('profile/reservation_cancel.html.twig', [
            'reservation' => $reservation,
            'canBeRefunded' => $canBeRefunded,
            'meta_description' => 'Annulez votre réservation sur regards singuliers',
            'page_title' => 'Annuler ma réservation - regards singuliers'
        ]);
    } */

    /**
     * Vérifie si une réservation peut être remboursée (> 72h avant le RDV)
     */
    private function canRefundReservation(Reservation $reservation): bool
    {
        $now = new \DateTime();
        $appointmentTime = $reservation->getAppointmentDatetime();
        $interval = $now->diff($appointmentTime);
        $hoursBeforeAppointment = ($interval->days * 24) + $interval->h;
        
        return $hoursBeforeAppointment > 72;
    }


    #[Route('/supprimer-mon-compte', name: 'profile_delete')]
    public function deleteAccount(
        Request $request,
        Security $security,
        AnonymizationService $anonymisationService,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {

        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour supprimer votre compte.');
        }

        $form = $this->createForm(DeleteAccountFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            
            // Vérifier le mot de passe
            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Mot de passe incorrect');
                return $this->redirectToRoute('profile_delete');
            }

            try {
                // Déconnecter l'utilisateur d'abord
                $security->logout(false);
                $request->getSession()->invalidate();
                
                // Anonymiser les données de l'utilisateur
                $anonymisationService->anonymiseUserData($user);
                
                // Ajouter le message flash
                /* $this->addFlash('success', 'Votre compte a été supprimé avec succès.'); */
                
                // Rediriger vers la page d'accueil
                return $this->redirectToRoute('home');
                
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la suppression du compte: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du compte.');
                return $this->redirectToRoute('profile_delete');
            }
        }

        return $this->render('profile/delete.html.twig', [
            'deleteForm' => $form->createView(),
            'meta_description' => 'Supprimez votre compte et toutes vos données associées de manière sécurisée.',
            'page_title' => 'Supprimer mon compte - regards singuliers'
        ]);
    }
}