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

    #[Route('/date/{slug:service}', name: 'reservation_date')]
    public function date(Service $service): Response
    {
        if (!$service) {
            return $this->redirectToRoute('home');
        }

        if (!$service->isActive()) {
            throw $this->createNotFoundException('Ce service n\'est pas disponible pour la réservation.');
        }

        return $this->render('reservation/date.html.twig', [
            'page_title' => 'Choisir une date - regards singuliers',
            'meta_description' => 'Sélectionnez votre date de rendez-vous avec notre architecte d\'intérieur. Un moment unique pour donner vie à votre projet de décoration.',
            'service' => $service,
            'calendly_url' => $this->getParameter('calendly.url'),
            'calendly_api_key' => $_ENV['CALENDLY_API_KEY']
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

            // Vérifier le token CSRF
            $token = $request->headers->get('X-CSRF-TOKEN');
            if (!$this->isCsrfTokenValid('process_date', $token)) {
                throw new InvalidCsrfTokenException('Token CSRF invalide');
            }

            // Valider les données requises
            if (!isset($data['serviceId'], $data['event']['event_id'], $data['event']['invitee_id'])) {
                throw new \Exception('Données manquantes');
            }

            $service = $this->serviceRepository->find($data['serviceId']);
            if (!$service) {
                throw new \Exception('Service non trouvé');
            }

            // Créer la réservation
            $reservation = $this->reservationService->createReservation($service, $this->getUser());
            
            // Sauvegarder les IDs Calendly
            $reservation->setCalendlyEventId($data['event']['event_id']);
            $reservation->setCalendlyInviteeId($data['event']['invitee_id']);

            // Récupérer les détails de l'événement via l'API Calendly
            $eventId = str_replace('https://api.calendly.com/scheduled_events/', '', $data['event']['event_id']);
            $response = $this->calendlyService->getEventDetails($eventId);
            
            if ($response && isset($response['resource']['start_time'])) {
                $startTime = new \DateTimeImmutable($response['resource']['start_time']);
                $reservation->setAppointmentDatetime($startTime);
            }
            
            // Définir le statut initial
            $reservation->setStatus('en attente');

            // Sauvegarder la réservation
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('reservation_payment', [
                    'slug' => $service->getSlug()
                ])
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/payment/{slug:service}', name: 'reservation_payment')]
    public function payment(Service $service): Response
    {
        $reservation = $this->reservationRepository->findOneBy([
            'user' => $this->getUser(),
            'service' => $service,
            'status' => 'en attente'
        ], ['bookedAt' => 'DESC']);

        if (!$reservation) {
            return $this->redirectToRoute('reservation_date', ['slug' => $service->getSlug()]);
        }

        $paymentData = $this->reservationService->createPaymentIntent($reservation);

        return $this->render('reservation/payment.html.twig', [
            'page_title' => 'Paiement de l\'acompte - regards singuliers',
            'meta_description' => 'Finalisez votre projet avec le paiement de l\'acompte. Une étape proche de la transformation de votre intérieur.',
            'service' => $service,
            'reservation' => $reservation,
            'deposit_amount' => $paymentData['depositAmount'],
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
            'client_secret' => $paymentData['clientSecret'],
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

        if ($reservation->getPayments()->isEmpty()) {
            $this->reservationService->handlePaymentSuccess($paymentIntentId);
        }

        return $this->render('reservation/success.html.twig', [
            'page_title' => 'Réservation confirmée - regards singuliers',
            'meta_description' => 'Votre réservation est confirmée. Première étape vers la métamorphose de votre espace de vie.',
            'reservation' => $reservation
        ]);
    }

    #[Route('/canceled', name: 'reservation_canceled')]
    public function canceled(): Response
    {
        return $this->render('reservation/canceled.html.twig', [
            'page_title' => 'Paiement annulé - regards singuliers',
            'meta_description' => 'Paiement interrompu. Votre projet reste notre priorité, nous sommes là pour vous accompagner.',
        ]);
    }
}