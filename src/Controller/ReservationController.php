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

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private $reservationService;
    private $reservationRepository;
    private $serviceRepository;
    private $entityManager;
    private $calendlyService;

    public function __construct(
        ReservationService $reservationService,
        ReservationRepository $reservationRepository,
        ServiceRepository $serviceRepository,
        EntityManagerInterface $entityManager,
        CalendlyService $calendlyService
    ) {
        $this->reservationService = $reservationService;
        $this->reservationRepository = $reservationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->entityManager = $entityManager;
        $this->calendlyService = $calendlyService;
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
                'status' => 'en attente'
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
            
            $reservation->setStatus('en attente');
            
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
            'status' => 'en attente'
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
        $paymentIntentId = $request->query->get('payment_intent');
        if (!$paymentIntentId) {
            throw $this->createNotFoundException('Payment Intent non trouvé');
        }

        $reservation = $this->reservationRepository->findByStripePaymentIntentId($paymentIntentId);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation');
        }

        if ($reservation->getStatus() !== 'confirmé') {
            $this->reservationService->handlePaymentSuccess($paymentIntentId);
            $reservation->setStatus('confirmé');
            $this->entityManager->flush();
        }

        return $this->render('reservation/success.html.twig', [
            'page_title' => 'Réservation confirmée - regards singuliers',
            'meta_description' => 'Votre réservation est confirmée.',
            'reservation' => $reservation
        ]);
    }

    #[Route('/canceled', name: 'reservation_canceled')]
    public function canceled(): Response
    {
        return $this->render('reservation/canceled.html.twig', [
            'page_title' => 'Paiement annulé - regards singuliers',
            'meta_description' => 'Paiement interrompu.',
        ]);
    }
}