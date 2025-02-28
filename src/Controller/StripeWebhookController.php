<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Service\BookingConfirmationService;
use Stripe\Webhook;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    private BookingRepository $bookingRepository;
    private BookingConfirmationService $bookingConfirmationService;
    private string $stripeWebhookSecret;

    public function __construct(
        BookingRepository $bookingRepository,
        BookingConfirmationService $bookingConfirmationService,
        private ParameterBagInterface $parameterBag
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->bookingConfirmationService = $bookingConfirmationService;
        
        // Récupérer les clés depuis les paramètres
        $stripeSecretKey = $this->parameterBag->get('stripe_secret_key');
        $this->stripeWebhookSecret = $this->parameterBag->get('stripe_webhook_secret');
        
        Stripe::setApiKey($stripeSecretKey);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                $this->stripeWebhookSecret
            );

            // Gérer l'événement de paiement réussi
            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                
                // Récupérer l'ID de réservation depuis les métadonnées
                $bookingId = $session->metadata->booking_id;
                
                // Récupérer la réservation
                $booking = $this->bookingRepository->find($bookingId);
                
                if ($booking) {
                    // Confirmer la réservation
                    $this->bookingConfirmationService->confirmBookingAfterPayment($booking, $session);
                }
            }

            return new Response('Webhook handled', Response::HTTP_OK);

        } catch (\UnexpectedValueException $e) {
            // Signature invalide
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Erreur de signature Stripe
            return new Response('Signature verification failed', Response::HTTP_BAD_REQUEST);
        }
    }
}
