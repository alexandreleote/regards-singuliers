<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Service\StripeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeTestController extends AbstractController
{
    #[Route('/stripe/test', name: 'app_stripe_test')]
    public function stripeTest(StripeService $stripeService): Response
    {
        try {
            // Vous pouvez créer un test de paiement factice
            Stripe::setApiKey($this->getParameter('app.stripe_secret_key'));
            
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => 1000, // 10€
                'currency' => 'eur',
                'payment_method_types' => ['card'],
            ]);

            return new JsonResponse([
                'status' => 'success',
                'client_secret' => $paymentIntent->client_secret
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
