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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class ReservationService
{
    private $entityManager;
    private $reservationRepository;
    private $paymentRepository;
    private $stripeClient;
    private $calendlyService;
    private $params;
    private $mailer;
    private $stripeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository,
        PaymentRepository $paymentRepository,
        CalendlyService $calendlyService,
        ParameterBagInterface $params,
        MailerInterface $mailer,
        StripeService $stripeService
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
        $this->paymentRepository = $paymentRepository;
        $this->calendlyService = $calendlyService;
        $this->params = $params;
        $this->mailer = $mailer;
        $this->stripeClient = new StripeClient($this->params->get('stripe.secret_key'));
        $this->stripeService = $stripeService;
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
        $randomId = strtoupper(substr(uniqid(), -8)); // Génère un identifiant unique de 4 caractères
        $billingNumber = $serviceRef . $userInitials . '-' . $randomId;
        $payment->setBillingNumber($billingNumber);

        // Mettre à jour le statut de la réservation
        $reservation->setStatus('confirmed');
        $reservation->addPayment($payment);

        // Sauvegarder les modifications
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $reservation;
    }

    private function sendCancellationEmail(Reservation $reservation, bool $willBeRefunded): void
    {
        try {
            $depositAmount = $reservation->getPrice() * 0.5;
            $userEmail = $reservation->getUser()->getEmail();

            error_log('Tentative d\'envoi d\'email d\'annulation à ' . $userEmail);
            error_log('Montant de l\'acompte : ' . $depositAmount);
            error_log('Remboursement prévu : ' . ($willBeRefunded ? 'oui' : 'non'));

            $email = (new TemplatedEmail())
                ->from('no-reply@regards-singuliers.com')
                ->to($userEmail)
                ->subject('Confirmation d\'annulation de votre réservation - regards singuliers')
                ->htmlTemplate('email/reservation_cancellation.html.twig')
                ->context([
                    'reservation' => $reservation,
                    'user' => $reservation->getUser(),
                    'service' => $reservation->getService(),
                    'refund_amount' => $depositAmount,
                    'will_be_refunded' => $willBeRefunded
                ]);

            error_log('Email créé avec succès, envoi en cours...');
            $this->mailer->send($email);
            error_log('Email d\'annulation envoyé avec succès à ' . $userEmail);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'envoi de l\'email d\'annulation : ' . $e->getMessage());
            error_log('Stack trace : ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function handlePaymentFailure(string $paymentIntentId): Reservation
    {
        $reservation = $this->reservationRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
        
        if (!$reservation) {
            throw new \RuntimeException('Réservation non trouvée');
        }

        $reservation->setStatus('canceled');
        $this->entityManager->flush();

        // Envoyer l'email d'annulation
        $this->sendCancellationEmail($reservation, false);

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
        // Récupérer le PaymentIntent associé à la réservation
        $paymentIntent = $this->stripeService->retrievePaymentIntent($reservation->getStripePaymentIntentId());

        // Créer le remboursement via StripeService
        $this->stripeService->refundPayment(
            $paymentIntent->id,
            $paymentIntent->amount / 100, // Conversion des centimes en euros
            'requested_by_customer'
        );

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
        // Annuler d'abord l'événement Calendly si un ID existe
        if ($reservation->getCalendlyEventId()) {
            try {
                $this->calendlyService->cancelEvent($reservation->getCalendlyEventId());
            } catch (\Exception $e) {
                // Log l'erreur mais continuer le processus d'annulation
                error_log('Erreur lors de l\'annulation Calendly: ' . $e->getMessage());
            }
        }

        // Vérifier si on est à plus de 72h du rendez-vous pour le remboursement
        $appointmentDate = $reservation->getAppointmentDatetime();
        $now = new \DateTimeImmutable();
        $interval = $now->diff($appointmentDate);
        $hoursUntilAppointment = ($interval->days * 24) + $interval->h;

        $willBeRefunded = false;

        // Si on est à plus de 72h, procéder au remboursement
        if ($hoursUntilAppointment > 72) {
            try {
                $this->refundPayment($reservation);
                $reservation->setStatus('refunded');
                $willBeRefunded = true;
            } catch (\Exception $e) {
                // Log l'erreur de remboursement
                error_log('Erreur lors du remboursement: ' . $e->getMessage());
                $reservation->setStatus('canceled');
            }
        } else {
            $reservation->setStatus('canceled');
            // Mettre à jour le statut du paiement en "not_refunded" si un paiement existe
            $payment = $reservation->getPayments()->first();
            if ($payment) {
                $payment->setPaymentStatus('not_refunded');
            }
        }

        // Enregistrer la date d'annulation
        $reservation->setCanceledAt($now);
        if ($payment = $reservation->getPayments()->first()) {
            $payment->setCanceledAt($now);
        }

        $this->entityManager->flush();

        // Envoyer l'email d'annulation
        $this->sendCancellationEmail($reservation, $willBeRefunded);
    }
} 