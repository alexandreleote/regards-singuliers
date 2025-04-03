<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Service;
use App\Entity\User;
use App\Entity\Payment;
use App\Repository\ReservationRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReservationService
{
    private $entityManager;
    private $reservationRepository;
    private $paymentRepository;
    private $stripeClient;
    private $calendlyService;
    private $params;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository,
        PaymentRepository $paymentRepository,
        CalendlyService $calendlyService,
        ParameterBagInterface $params
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
        $this->paymentRepository = $paymentRepository;
        $this->calendlyService = $calendlyService;
        $this->params = $params;
        $this->stripeClient = new StripeClient($this->params->get('stripe.secret_key'));
    }

    public function createReservation(Service $service, User $user): Reservation
    {
        $reservation = new Reservation();
        $reservation->setService($service);
        $reservation->setUser($user);
        $reservation->setPrice($service->getPrice());
        $reservation->setStatus('en attente');

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return $reservation;
    }

    public function createPaymentIntent(Reservation $reservation): array
    {
        $depositAmount = $reservation->getPrice() * 0.5; // 50% du prix total

        $paymentIntent = $this->stripeClient->paymentIntents->create([
            'amount' => $depositAmount * 100, // Stripe utilise les centimes
            'currency' => 'eur',
            'metadata' => [
                'reservation_id' => $reservation->getId(),
                'service_id' => $reservation->getService()->getId(),
                'user_id' => $reservation->getUser()->getId(),
            ],
        ]);

        $reservation->setStripePaymentIntentId($paymentIntent->id);
        $this->entityManager->flush();

        return [
            'clientSecret' => $paymentIntent->client_secret,
            'depositAmount' => $depositAmount,
        ];
    }

    public function handlePaymentSuccess(string $paymentIntentId): Reservation
    {
        $reservation = $this->reservationRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        
        if (!$reservation) {
            throw new \RuntimeException('Réservation non trouvée');
        }

        $payment = new Payment();
        $payment->setReservation($reservation);
        $payment->setStripePaymentId($paymentIntentId);
        $payment->setTotalAmount($reservation->getPrice());
        $payment->setDepositAmount($reservation->getPrice() * 0.5);
        $payment->setPaymentStatus('completed');
        $payment->setValidationStatus('validated');
        $payment->setPaidAt(new \DateTimeImmutable());

        $reservation->setStatus('confirmed');

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $reservation;
    }

    public function handlePaymentFailure(string $paymentIntentId): Reservation
    {
        $reservation = $this->reservationRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        
        if (!$reservation) {
            throw new \RuntimeException('Réservation non trouvée');
        }

        $reservation->setStatus('canceled');
        $this->entityManager->flush();

        return $reservation;
    }

    public function scheduleAppointment(Reservation $reservation, \DateTimeImmutable $appointmentDate): void
    {
        $reservation->setBookedAt($appointmentDate);
        $reservation->setStatus('scheduled');
        
        $this->calendlyService->createEvent($reservation);
        $this->entityManager->flush();
    }

    public function refundPayment(Reservation $reservation): void
    {
        $stripe = new \Stripe\StripeClient($this->params->get('stripe.secret_key'));

        // Récupérer le PaymentIntent associé à la réservation
        $paymentIntent = $stripe->paymentIntents->retrieve($reservation->getStripePaymentIntentId());

        // Créer le remboursement
        $stripe->refunds->create([
            'payment_intent' => $paymentIntent->id,
            'amount' => $paymentIntent->amount, // Montant en centimes
            'reason' => 'requested_by_customer'
        ]);
    }
} 