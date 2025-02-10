<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\Reservation;
use App\Service\StripeService;
use App\Form\ReservationDateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ReservationController extends AbstractController
{
    #[Route('/reservation/{id}/payment', name: 'app_reservation_payment')]
    public function payment(
        Reservation $reservation,
        StripeService $stripeService
    ): Response {
        // Vérifier que l'utilisateur est bien le propriétaire de la réservation
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier que la réservation est en statut 'pending'
        if ($reservation->getStatus() !== 'pending') {
            throw $this->createNotFoundException('Cette réservation ne peut plus être payée');
        }

        try {
            $paymentIntent = $stripeService->createPaymentIntent($reservation);
            
            return $this->render('reservation/payment.html.twig', [
                'reservation' => $reservation,
                'clientSecret' => $paymentIntent['clientSecret'], // Notez le changement ici
                'publicKey' => $stripeService->getPublicKey()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la préparation du paiement');
            return $this->redirectToRoute('services_list');
        }
    }

    // Ajouter une route pour le webhook Stripe
    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(
        Request $request,
        StripeService $stripeService,
        ParameterBagInterface $params
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $webhookSecret = $params->get('stripe_webhook_secret');

        try {
            $stripeService->handleWebhook($payload, $sigHeader, $webhookSecret);
            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Stripe webhook error: ' . $e->getMessage());
            return new Response('Webhook error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    // Ajouter une route pour la confirmation de paiement
    #[Route('/reservation/{id}/confirmation', name: 'app_reservation_confirmation')]
    public function confirmation(Reservation $reservation): Response
    {
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('reservation/confirmation.html.twig', [
            'reservation' => $reservation
        ]);
    }
    
}