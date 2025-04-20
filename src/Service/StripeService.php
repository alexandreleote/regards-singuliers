<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;

class StripeService
{
    private $stripeClient;
    
    public function __construct(string $stripeSecretKey)
    {
        $this->stripeClient = new StripeClient($stripeSecretKey);
        Stripe::setApiKey($stripeSecretKey);
    }
    
    public function createPaymentIntent(float $amount, string $currency = 'eur'): PaymentIntent
    {
        return $this->stripeClient->paymentIntents->create([
            'amount' => (int)($amount * 100),
            'currency' => $currency,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripeClient->paymentIntents->retrieve($paymentIntentId);
    }

    public function refundPayment(string $paymentIntentId, float $amount, string $reason = 'requested_by_customer'): void
    {
        try {
            $this->stripeClient->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount' => (int)($amount * 100),
                'reason' => $reason
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors du remboursement: ' . $e->getMessage());
        }
    }

    public function cancelPaymentIntent(string $paymentIntentId): void
    {
        try {
            $this->stripeClient->paymentIntents->cancel($paymentIntentId);
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de l\'annulation du paiement: ' . $e->getMessage());
        }
    }
}
