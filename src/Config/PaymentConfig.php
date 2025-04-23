<?php

namespace App\Config;

class PaymentConfig
{
    private string $stripePublicKey;
    private string $stripeSecretKey;
    private string $calendlyUrl;

    public function __construct(
        string $stripePublicKey,
        string $stripeSecretKey,
        string $calendlyUrl
    ) {
        $this->stripePublicKey = $stripePublicKey;
        $this->stripeSecretKey = $stripeSecretKey;
        $this->calendlyUrl = $calendlyUrl;
    }

    public function getStripePublicKey(): string
    {
        return $this->stripePublicKey;
    }

    public function getStripeSecretKey(): string
    {
        return $this->stripeSecretKey;
    }

    public function getCalendlyUrl(): string
    {
        return $this->calendlyUrl;
    }
} 