<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Reservation;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripeService
{
    private const CURRENCY = 'eur';
    private const DEPOSIT_PERCENTAGE = 0.3;
    private const REMAINING_PERCENTAGE = 0.7;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $publicKey,
        private readonly string $architectAccountId,
    ) {
        Stripe::setApiKey($this->secretKey);
    }

    public static function create(ParameterBagInterface $params): self
    {
        return new self(
            $params->get('stripe_secret_key'),
            $params->get('stripe_public_key'),
            $params->get('stripe_architect_account_id')
        );
    }

    /**
     * Crée une intention de paiement pour l'acompte initial
     * 
     * @throws ApiErrorException Si une erreur survient lors de la création du paiement
     */
    public function createDepositPaymentIntent(Reservation $reservation, string $paymentMethodId): PaymentIntent
    {
        $this->validateReservation($reservation);
        
        return PaymentIntent::create([
            'amount' => $this->calculateAmount($reservation->getPrice(), self::DEPOSIT_PERCENTAGE),
            'currency' => self::CURRENCY,
            'payment_method' => $paymentMethodId,
            'confirmation_method' => 'manual',
            'confirm' => true,
            'transfer_data' => [
                'destination' => $this->architectAccountId,
            ],
            'metadata' => [
                'reservation_id' => (string)$reservation->getId(),
                'payment_type' => 'deposit',
                'client_email' => $reservation->getUser()->getEmail(),
                'amount_percentage' => self::DEPOSIT_PERCENTAGE * 100 . '%',
            ],
        ]);
    }

    /**
     * Crée une intention de paiement pour le solde restant
     * 
     * @throws ApiErrorException Si une erreur survient lors de la création du paiement
     */
    public function createRemainingPaymentIntent(Reservation $reservation, string $paymentMethodId): PaymentIntent
    {
        $this->validateReservation($reservation);
        $paymentDate = $reservation->calculateRemainingPaymentDate();

        return PaymentIntent::create([
            'amount' => $this->calculateAmount($reservation->getPrice(), self::REMAINING_PERCENTAGE),
            'currency' => self::CURRENCY,
            'payment_method' => $paymentMethodId,
            'confirmation_method' => 'manual',
            'confirm' => false,
            'setup_future_usage' => 'off_session',
            'transfer_data' => [
                'destination' => $this->architectAccountId,
            ],
            'metadata' => [
                'reservation_id' => (string)$reservation->getId(),
                'payment_type' => 'remaining',
                'scheduled_date' => $paymentDate->format('Y-m-d H:i:s'),
                'client_email' => $reservation->getUser()->getEmail(),
                'amount_percentage' => self::REMAINING_PERCENTAGE * 100 . '%',
            ],
            'payment_method_options' => [
                'card' => [
                    'capture_method' => 'manual',
                ],
            ],
        ]);
    }

    /**
     * Capture un paiement préautorisé
     * 
     * @throws ApiErrorException Si une erreur survient lors de la capture
     */
    public function capturePayment(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId)->capture();
    }

    /**
     * Effectue un remboursement
     * 
     * @throws ApiErrorException Si une erreur survient lors du remboursement
     */
    public function refundPayment(Payment $payment): bool
    {
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $payment->getStripePaymentId(),
                'reverse_transfer' => true,
            ]);
            
            if ($refund->status === 'succeeded') {
                $payment->setPaymentStatus('refunded');
                return true;
            }
            
            return false;
        } catch (ApiErrorException $e) {
            // Log l'erreur ici
            return false;
        }
    }

    /**
     * Met à jour le statut d'un paiement après succès
     */
    public function handlePaymentSuccess(Payment $payment): void
    {
        $payment->setValidationStatus(true);
        $payment->setPaymentStatus('completed');
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Valide qu'une réservation peut être utilisée pour un paiement
     * 
     * @throws \InvalidArgumentException Si la réservation n'est pas valide
     */
    private function validateReservation(Reservation $reservation): void
    {
        if (!$reservation->getId()) {
            throw new \InvalidArgumentException('La réservation doit être persistée avant de créer un paiement.');
        }

        if (!$reservation->getUser()) {
            throw new \InvalidArgumentException('La réservation doit être associée à un utilisateur.');
        }
    }

    /**
     * Calcule le montant en centimes pour un pourcentage donné du prix total
     */
    private function calculateAmount(float $price, float $percentage): int
    {
        return (int)($price * $percentage * 100);
    }
}
