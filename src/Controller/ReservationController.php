<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Reservation;
use App\Entity\Service;
use App\Service\CalendlyService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripeService $stripeService,
        private CalendlyService $calendlyService
    ) {}

    /**
     * Affiche la liste des services.
     *
     * @return Response La liste des services
     */
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(): Response
    {
        $services = $this->entityManager->getRepository(Service::class)->findAll();
        
        return $this->render('reservation/index.html.twig', [
            'services' => $services,
            'stripe_public_key' => $this->stripeService->getPublicKey(),
        ]);
    }

    /**
     * Récupère les créneaux disponibles.
     *
     * @param Request $request La requête HTTP
     * @return JsonResponse Les créneaux disponibles
     */
    #[Route('/slots', name: 'app_reservation_slots', methods: ['GET'])]
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date'));
        $endDate = new \DateTime($request->query->get('end_date'));
        
        $slots = $this->calendlyService->getAvailableSlots($startDate, $endDate);
        
        return $this->json($slots);
    }

    /**
     * Prépare le paiement initial.
     *
     * @param Request $request La requête HTTP
     * @return JsonResponse Les détails du paiement
     */
    #[Route('/setup-payment', name: 'app_reservation_setup_payment', methods: ['POST'])]
    public function setupPayment(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Créer ou récupérer le client Stripe
        $customer = $this->stripeService->createOrGetCustomer($user->getEmail());
        $user->setStripeCustomerId($customer->id);
        $this->entityManager->flush();

        // Créer un Setup Intent pour sauvegarder la carte
        $setupIntent = $this->stripeService->createSetupIntent($customer->id);

        return $this->json([
            'client_secret' => $setupIntent->client_secret,
        ]);
    }

    /**
     * Crée une nouvelle réservation.
     *
     * @param Request $request La requête HTTP
     * @return JsonResponse Le résultat de la création de réservation
     */
    #[Route('/create', name: 'app_reservation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user->isVerified()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Action non autorisée'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        
        $service = $this->entityManager->getRepository(Service::class)->find($data['service_id']);
        if (!$service) {
            return $this->json(['error' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setUser($this->getUser());
        $reservation->setService($service);
        $reservation->setBookingDate(new \DateTime($data['booking_date']));
        $reservation->setStatus('pending');
        $reservation->setPrice($service->getPrice());

        // Créer le paiement initial (30%)
        $paymentIntent = $this->stripeService->createInitialPayment($reservation, $data['payment_method_id']);

        // Créer le paiement
        $payment = new Payment();
        $payment->setStripePaymentId($paymentIntent->id);
        $payment->setAmount($service->getPrice());
        $payment->setDepositAmount($service->getPrice() * 0.3);
        $payment->setPaymentStatus('pending');
        $payment->setPaymentDate(new \DateTime());
        $payment->setValidationStatus(false);
        $payment->setReservation($reservation);

        // Programmer le paiement restant (70%) pour le lendemain du RDV
        $this->stripeService->scheduleRemainingPayment($reservation, $data['payment_method_id']);

        $this->entityManager->persist($reservation);
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'reservation_id' => $reservation->getId(),
        ]);
    }

    /**
     * Confirme le paiement d'une réservation.
     *
     * @param Reservation $reservation La réservation à confirmer
     * @return JsonResponse Le résultat de la confirmation
     */
    #[Route('/confirm/{id}', name: 'app_reservation_confirm', methods: ['POST'])]
    public function confirmPayment(Reservation $reservation): JsonResponse
    {
        $payment = $reservation->getPayment();
        
        if (!$payment) {
            return $this->json(['error' => 'Payment not found'], Response::HTTP_NOT_FOUND);
        }

        $this->stripeService->handlePaymentSuccess($payment);
        $reservation->setStatus('confirmed');

        // Créer l'événement Calendly
        $event = $this->calendlyService->createEvent(
            $this->getUser()->getEmail(),
            $reservation->getBookingDate(),
            'consultation'
        );

        $this->entityManager->flush();

        return $this->json(['status' => 'success']);
    }

    /**
     * Annule une réservation.
     *
     * @param Reservation $reservation La réservation à annuler
     * @return JsonResponse Le résultat de l'annulation
     */
    #[Route('/cancel/{id}', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(Reservation $reservation): JsonResponse
    {
        $now = new \DateTime();
        $bookingDate = $reservation->getBookingDate();
        $interval = $now->diff($bookingDate);
        
        // Récupérer le paiement restant programmé
        $remainingPaymentIntent = $this->stripeService->getRemainingPaymentIntent($reservation);
        
        if ($interval->days >= 3) {
            // Plus de 72h avant le RDV
            // 1. Rembourser l'acompte
            $payment = $reservation->getPayment();
            $refundSuccess = false;
            if ($payment) {
                $refundSuccess = $this->stripeService->processRefund($payment);
            }
            
            // 2. Annuler le paiement restant
            $cancelSuccess = false;
            if ($remainingPaymentIntent) {
                $cancelSuccess = $this->stripeService->cancelRemainingPayment($remainingPaymentIntent->id);
            }

            if ($refundSuccess || $cancelSuccess) {
                $reservation->setStatus('cancelled_with_refund');
                $this->entityManager->flush();
                
                // Annuler le RDV dans Calendly si nécessaire
                $this->calendlyService->cancelEvent($reservation->getCalendlyEventId());
                
                return $this->json([
                    'status' => 'success',
                    'message' => 'Reservation cancelled with refund'
                ]);
            }
        } else {
            // Moins de 72h avant le RDV
            // 1. Pas de remboursement de l'acompte
            // 2. Annuler uniquement le paiement restant
            if ($remainingPaymentIntent) {
                $this->stripeService->cancelRemainingPayment($remainingPaymentIntent->id);
            }
            
            $reservation->setStatus('cancelled_no_refund');
            $this->entityManager->flush();
            
            // Annuler le RDV dans Calendly si nécessaire
            $this->calendlyService->cancelEvent($reservation->getCalendlyEventId());
            
            return $this->json([
                'status' => 'success',
                'message' => 'Reservation cancelled without refund'
            ]);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Could not process cancellation'
        ], Response::HTTP_BAD_REQUEST);
    }
}
