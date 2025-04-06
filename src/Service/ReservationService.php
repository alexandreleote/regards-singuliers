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
        // Vérifier si les champs requis sont vides
        if (empty($user->getName()) || empty($user->getFirstName()) || empty($user->getAddress())) {
            throw new \RuntimeException('Veuillez compléter votre profil (nom, prénom et adresse) avant de faire une réservation.');
        }

        $reservation = new Reservation();
        $reservation->setService($service);
        $reservation->setUser($user);
        $reservation->setPrice($service->getPrice());
        $reservation->setStatus('en attente');
        $reservation->setName($user->getName());
        $reservation->setFirstName($user->getFirstName());

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

        // Vérifier si un paiement existe déjà pour cette réservation
        $existingPayment = $this->paymentRepository->findOneBy(['reservation' => $reservation]);
        if ($existingPayment) {
            throw new \RuntimeException('Un paiement existe déjà pour cette réservation');
        }

        // Créer le nouveau paiement
        $payment = new Payment();
        $payment->setReservation($reservation);
        $payment->setStripePaymentId($paymentIntentId);
        $payment->setTotalAmount($reservation->getPrice());
        $payment->setDepositAmount($reservation->getPrice() * 0.5);
        $payment->setPaymentStatus('completed');
        $payment->setValidationStatus('validated');
        $payment->setPaidAt(new \DateTimeImmutable());
        
        // Hydrater les informations de facturation
        $user = $reservation->getUser();
        $payment->setName($user->getName());
        $payment->setFirstName($user->getFirstName());
        $payment->setBillingAddress($user->getAddress());
        $payment->setBillingDate(new \DateTimeImmutable());
        
        // Générer le numéro de facturation
        $serviceRef = strtoupper(substr($reservation->getService()->getTitle(), 0, 3));
        $userInitials = strtoupper(substr($user->getFirstName(), 0, 1) . substr($user->getName(), 0, 1));
        $date = (new \DateTimeImmutable())->format('Ymd');
        $billingNumber = $serviceRef . $userInitials . $date;
        $payment->setBillingNumber($billingNumber);

        // Mettre à jour le statut de la réservation
        $reservation->setStatus('confirmed');
        $reservation->addPayment($payment);

        // Sauvegarder les modifications
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

        // Mettre à jour le statut du paiement
        $payment = $reservation->getPayments()->first();
        if ($payment) {
            $payment->setPaymentStatus('refunded');
            $this->entityManager->flush();
        }
    }

    /**
     * Annule une réservation et gère le remboursement et l'annulation Calendly
     */
    public function cancelReservation(Reservation $reservation): void
    {
        // Vérifier si on est à plus de 72h du rendez-vous pour le remboursement
        $appointmentDate = $reservation->getAppointmentDatetime();
        $now = new \DateTime();
        $interval = $now->diff($appointmentDate);
        $hoursUntilAppointment = $interval->h + ($interval->days * 24);

        // Si on est à plus de 72h, procéder au remboursement
        if ($hoursUntilAppointment >= 72) {
            $this->refundPayment($reservation);
            $reservation->setStatus('refunded');
        } else {
            $reservation->setStatus('canceled');
        }

        // Annuler l'événement Calendly si un ID existe
        if ($reservation->getCalendlyEventId()) {
            try {
                $this->calendlyService->cancelEvent($reservation->getCalendlyEventId());
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas bloquer le processus d'annulation
                error_log('Erreur lors de l\'annulation Calendly: ' . $e->getMessage());
            }
        }

        $this->entityManager->flush();
    }
} 