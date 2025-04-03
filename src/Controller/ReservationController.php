<?php

namespace App\Controller;

use App\Entity\Service;
use App\Service\ReservationService;
use App\Repository\ReservationRepository;
use App\Repository\ServiceRepository;
use App\Service\CalendlyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use App\Security\EmailVerifier;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private $reservationService;
    private $reservationRepository;
    private $serviceRepository;
    private $entityManager;
    private $calendlyService;
    private $emailVerifier;

    public function __construct(
        ReservationService $reservationService,
        ReservationRepository $reservationRepository,
        ServiceRepository $serviceRepository,
        EntityManagerInterface $entityManager,
        CalendlyService $calendlyService,
        EmailVerifier $emailVerifier
    ) {
        $this->reservationService = $reservationService;
        $this->reservationRepository = $reservationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->entityManager = $entityManager;
        $this->calendlyService = $calendlyService;
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/date/{slug}', name: 'reservation_date')]
    public function date(string $slug): Response
    {
        $service = $this->serviceRepository->findOneBy(['slug' => $slug]);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }

        if (!$service->isActive()) {
            throw $this->createNotFoundException('Ce service n\'est pas disponible pour la réservation.');
        }

        return $this->render('reservation/date.html.twig', [
            'page_title' => 'Choisir une date - regards singuliers',
            'meta_description' => 'Sélectionnez votre date de rendez-vous avec notre architecte d\'intérieur.',
            'service' => $service,
            'calendly_url' => $this->getParameter('calendly.url')
        ]);
    }

    #[Route('/process-date', name: 'reservation_process_date', methods: ['POST'])]
    public function processDate(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
           
            if (!$data) {
                throw new \Exception('Données JSON invalides');
            }

            if (!isset($data['serviceId'], $data['event']['event_id'], $data['event']['invitee_id'])) {
                throw new \Exception('Données manquantes');
            }

            $service = $this->serviceRepository->find($data['serviceId']);
            if (!$service) {
                throw new \Exception('Service non trouvé');
            }

            // Vérifier si une réservation en attente existe déjà
            $existingReservation = $this->reservationRepository->findOneBy([
                'user' => $this->getUser(),
                'service' => $service,
                'status' => 'waiting'
            ]);

            if ($existingReservation) {
                // Mettre à jour la réservation existante
                $reservation = $existingReservation;
            } else {
                // Créer une nouvelle réservation
                $reservation = $this->reservationService->createReservation($service, $this->getUser());
            }
            
            $reservation->setCalendlyEventId($data['event']['event_id']);
            $reservation->setCalendlyInviteeId($data['event']['invitee_id']);

            $eventId = str_replace('https://api.calendly.com/scheduled_events/', '', $data['event']['event_id']);
            $response = $this->calendlyService->getEventDetails($eventId);
            
            if ($response && isset($response['resource']['start_time'])) {
                $startTime = new \DateTimeImmutable($response['resource']['start_time']);
                $reservation->setAppointmentDatetime($startTime);
            }
            
            $reservation->setStatus('waiting');
            
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'reservationId' => $reservation->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/paiement/{slug}', name: 'reservation_payment')]
    public function payment(string $slug): Response
    {
        $service = $this->serviceRepository->findOneBy(['slug' => $slug]);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }

        $reservation = $this->reservationRepository->findOneBy([
            'user' => $this->getUser(),
            'service' => $service,
            'status' => 'waiting'
        ], ['bookedAt' => 'DESC']); // Changé de createdAt à bookedAt

        if (!$reservation) {
            return $this->redirectToRoute('reservation_date', ['slug' => $service->getSlug()]);
        }

        $paymentData = $this->reservationService->createPaymentIntent($reservation);

        return $this->render('reservation/payment.html.twig', [
            'page_title' => 'Paiement de l\'acompte - regards singuliers',
            'meta_description' => 'Finalisez votre projet avec le paiement de l\'acompte.',
            'service' => $service,
            'reservation' => $reservation,
            'deposit_amount' => $paymentData['depositAmount'],
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
            'client_secret' => $paymentData['clientSecret']
        ]);
    }

    #[Route('/success', name: 'reservation_success')]
    public function success(Request $request): Response
    {
        $reservation = $this->reservationRepository->findOneBy([
            'user' => $this->getUser(),
            'status' => 'waiting'
        ], ['bookedAt' => 'DESC']);

        if (!$reservation) {
            return $this->redirectToRoute('profile_reservations');
        }

        // Utiliser le ReservationService pour gérer le succès du paiement
        $reservation = $this->reservationService->handlePaymentSuccess($reservation->getStripePaymentIntentId());

        // Calculer le montant de l'acompte (50% du prix total)
        $depositAmount = $reservation->getPrice() * 0.5;

        // Créer l'email de confirmation
        $email = (new TemplatedEmail())
            ->from('no-reply@regards-singuliers.com')
            ->to($reservation->getUser()->getEmail())
            ->subject('Confirmation de votre réservation - regards singuliers')
            ->htmlTemplate('email/reservation_confirmation.html.twig')
            ->context([
                'reservation' => $reservation,
                'user' => $reservation->getUser(),
                'service' => $reservation->getService(),
                'deposit_amount' => $depositAmount
            ]);

        $this->emailVerifier->sendEmailConfirmation('reservation_confirmation', $reservation->getUser(), $email);

        return $this->render('reservation/success.html.twig', [
            'page_title' => 'Réservation confirmée - regards singuliers',
            'meta_description' => 'Votre réservation a été confirmée avec succès. Nous vous avons envoyé un email de confirmation.',
            'reservation' => $reservation,
            'deposit_amount' => $depositAmount
        ]);
    }

    #[Route('/canceled', name: 'reservation_canceled')]
    public function canceled(Request $request): Response
    {
        // Récupérer la réservation en attente la plus récente de l'utilisateur
        $reservation = $this->reservationRepository->findOneBy([
            'user' => $this->getUser(),
            'status' => 'waiting'
        ], ['bookedAt' => 'DESC']);

        if ($reservation) {
            $reservation->setStatus('canceled');
            $this->entityManager->flush();
        }

        return $this->render('reservation/canceled.html.twig', [
            'page_title' => 'Paiement annulé - regards singuliers',
            'meta_description' => 'Paiement interrompu.',
            'reservation' => $reservation
        ]);
    }

    #[Route('/annulation/{id}', name: 'reservation_cancel_confirmed')]
    public function cancelConfirmedReservation(Request $request, Reservation $reservation): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire de la réservation
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation');
        }

        // Vérifier que la réservation peut être annulée
        if (in_array($reservation->getStatus(), ['canceled', 'refunded'])) {
            throw $this->createAccessDeniedException('Cette réservation ne peut pas être annulée');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel_reservation_'.$reservation->getId(), $request->request->get('_token'))) {
            throw new InvalidCsrfTokenException('Token CSRF invalide');
        }

        // Calculer le montant de l'acompte pour l'email
        $depositAmount = $reservation->getPrice() * 0.5;

        // Gérer l'annulation selon le statut
        if ($reservation->getStatus() === 'confirmed') {
            $appointmentDate = $reservation->getAppointmentDatetime();
            $now = new \DateTime();
            $interval = $now->diff($appointmentDate);
            $hoursUntilAppointment = $interval->h + ($interval->days * 24);

            // Vérifier si on est à plus de 72h du rendez-vous pour le remboursement
            if ($hoursUntilAppointment >= 72) {
                // Créer un remboursement via Stripe
                try {
                    $this->reservationService->refundPayment($reservation);
                    $reservation->setStatus('refunded');
                    $this->entityManager->flush();

                    // Envoyer un email de confirmation d'annulation avec remboursement
                    $email = (new TemplatedEmail())
                        ->from('no-reply@regards-singuliers.com')
                        ->to($reservation->getUser()->getEmail())
                        ->subject('Confirmation d\'annulation de votre réservation - regards singuliers')
                        ->htmlTemplate('email/reservation_cancellation.html.twig')
                        ->context([
                            'reservation' => $reservation,
                            'user' => $reservation->getUser(),
                            'service' => $reservation->getService(),
                            'refund_amount' => $depositAmount,
                            'will_be_refunded' => true
                        ]);

                    $this->emailVerifier->sendEmailConfirmation('reservation_cancellation', $reservation->getUser(), $email);

                    $this->addFlash('success', 'Votre réservation a été annulée et vous serez remboursé de l\'acompte.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du remboursement. Veuillez nous contacter.');
                }
            } else {
                // Annulation sans remboursement
                $reservation->setStatus('canceled');
                $this->entityManager->flush();

                // Envoyer un email de confirmation d'annulation sans remboursement
                $email = (new TemplatedEmail())
                    ->from('no-reply@regards-singuliers.com')
                    ->to($reservation->getUser()->getEmail())
                    ->subject('Confirmation d\'annulation de votre réservation - regards singuliers')
                    ->htmlTemplate('email/reservation_cancellation.html.twig')
                    ->context([
                        'reservation' => $reservation,
                        'user' => $reservation->getUser(),
                        'service' => $reservation->getService(),
                        'refund_amount' => $depositAmount,
                        'will_be_refunded' => false
                    ]);

                $this->emailVerifier->sendEmailConfirmation('reservation_cancellation', $reservation->getUser(), $email);

                $this->addFlash('warning', 'Votre réservation a été annulée. Aucun remboursement ne sera effectué car l\'annulation est intervenue à moins de 72h du rendez-vous.');
            }
        } else {
            // Annulation d'une réservation en attente
            $reservation->setStatus('canceled');
            $this->entityManager->flush();

            // Envoyer un email de confirmation d'annulation
            $email = (new TemplatedEmail())
                ->from('no-reply@regards-singuliers.com')
                ->to($reservation->getUser()->getEmail())
                ->subject('Confirmation d\'annulation de votre réservation - regards singuliers')
                ->htmlTemplate('email/reservation_cancellation.html.twig')
                ->context([
                    'reservation' => $reservation,
                    'user' => $reservation->getUser(),
                    'service' => $reservation->getService(),
                    'refund_amount' => 0,
                    'will_be_refunded' => false
                ]);

            $this->emailVerifier->sendEmailConfirmation('reservation_cancellation', $reservation->getUser(), $email);

            $this->addFlash('info', 'Votre réservation en attente a été annulée.');
        }

        return $this->redirectToRoute('profile_reservations');
    }
}