<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Entity\Reservation;

class StripeService
{
    private string $secretKey;
    private string $publicKey;

    public function __construct(string $secretKey, string $publicKey)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
    }

    public function createPaymentIntent(Reservation $reservation): PaymentIntent
    {
        Stripe::setApiKey($this->secretKey);

        // Calculer l'acompte (30% par exemple)
        $depositAmount = $reservation->getPrice() * 0.3;

        return PaymentIntent::create([
            'amount' => (int)($depositAmount * 100),
            'currency' => 'eur',
            'metadata' => [
                'reservation_id' => $reservation->getId()
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }
}