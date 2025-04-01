<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Refund;

class StripeService
{
    private $secretKey;
    
    public function __construct(string $stripeSecretKey)
    {
        $this->secretKey = $stripeSecretKey;
        Stripe::setApiKey($this->secretKey);
    }
    
    public function refundPayment(string $paymentIntentId, float $amount): void
    {
        try {
            Refund::create([
                'payment_intent' => $paymentIntentId,
                'amount' => (int)($amount * 100), // Conversion en centimes
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors du remboursement: ' . $e->getMessage());
        }
    }
}
