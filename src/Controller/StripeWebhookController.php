<?php

namespace App\Controller;

use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private ReservationService $reservationService
    ) {
    }

    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $event = null;
        $payload = $request->getContent();
        $sig_header = $request->headers->get('stripe-signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $_ENV['STRIPE_WEBHOOK_SECRET']
            );
        } catch(\UnexpectedValueException $e) {
            return new Response('', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $this->reservationService->handlePaymentSuccess($paymentIntent->id);
        }

        return new Response('', 200);
    }
} 