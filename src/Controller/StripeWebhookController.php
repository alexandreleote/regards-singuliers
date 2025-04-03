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

        // Gérer les différents types d'événements Stripe
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                try {
                    $this->reservationService->handlePaymentSuccess($paymentIntent->id);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas échouer le webhook
                    error_log('Erreur lors du traitement du paiement : ' . $e->getMessage());
                }
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                try {
                    $this->reservationService->handlePaymentFailure($paymentIntent->id);
                } catch (\Exception $e) {
                    error_log('Erreur lors du traitement de l\'échec du paiement : ' . $e->getMessage());
                }
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                try {
                    // Mettre à jour le statut de la réservation en 'refunded'
                    $reservation = $this->reservationService->handleRefund($charge->payment_intent);
                    if ($reservation) {
                        $reservation->setStatus('refunded');
                        $this->entityManager->flush();
                    }
                } catch (\Exception $e) {
                    error_log('Erreur lors du traitement du remboursement : ' . $e->getMessage());
                }
                break;
        }

        return new Response('', 200);
    }
} 