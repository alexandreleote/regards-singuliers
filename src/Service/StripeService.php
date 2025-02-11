<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Reservation;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripeService
{
    private StripeClient $stripe;
    private string $publicKey;
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        string $secretKey,
        string $publicKey
    ) {
        $this->stripe = new StripeClient($secretKey);
        $this->publicKey = $publicKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function createPaymentIntent(Reservation $reservation): array
    {
        // Chercher d'abord si le produit existe déjà
        $productId = 'service_' . $reservation->getService()->getId();
        try {
            $product = $this->stripe->products->retrieve($productId);
        } catch (\Exception $e) {
            // Créer le produit s'il n'existe pas
            $product = $this->stripe->products->create([
                'id' => $productId,
                'name' => $reservation->getService()->getTitle(),
                'description' => $reservation->getService()->getDescription(),
                'metadata' => [
                    'service_id' => $reservation->getService()->getId(),
                ],
            ]);
        }

        // Log de débogage
        error_log('Création de l\'intention de paiement pour la réservation : ' . $reservation->getId());
        error_log('Montant : ' . $reservation->getPrice());

        try {
            // Créer le prix
            $price = $this->stripe->prices->create([
                'product' => $product->id,
                'unit_amount' => (int)($reservation->getPrice() * 100),
                'currency' => 'eur',
            ]);

            // Créer l'intention de paiement
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int)($reservation->getPrice() * 100),
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'reservation_id' => $reservation->getId(),
                    'service_id' => $reservation->getService()->getId(),
                    'user_id' => $reservation->getUser()->getId(),
                ],
            ]);

            error_log('PaymentIntent créé : ' . $paymentIntent->id);

            // Créer l'entité Payment
            $payment = new Payment();
            $payment->setStripePaymentId($paymentIntent->id);
            $payment->setAmount($reservation->getPrice());
            $payment->setDepositAmount($reservation->getPrice() * 0.3);
            $payment->setPaymentStatus('pending');
            $payment->setPaymentDate(new \DateTimeImmutable());
            $payment->setValidationStatus(false);
            $payment->setReservation($reservation);

            try {
                $this->entityManager->persist($payment);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                throw new \Exception('Erreur lors de la création du paiement : ' . $e->getMessage());
            }

            return [
                'clientSecret' => $paymentIntent->client_secret,
                'paymentId' => $payment->getId(),
            ];
        } catch (\Exception $e) {
            // Log détaillé de l'erreur
            error_log('Erreur Stripe : ' . $e->getMessage());
            error_log('Trace : ' . $e->getTraceAsString());
            
            throw $e;
        }
    }

    public function handleWebhook(string $payload, string $sigHeader, string $webhookSecret): void
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                $webhookSecret
            );

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->handleSuccessfulPayment($paymentIntent);
                    break;
                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handleFailedPayment($paymentIntent);
                    break;
                case 'payment_intent.canceled':
                    $paymentIntent = $event->data->object;
                    $this->handleCanceledPayment($paymentIntent);
                    break;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function handleSuccessfulPayment($paymentIntent): void
    {
        $payment = $this->findPaymentByStripeId($paymentIntent->id);
        if ($payment) {
            $payment->setPaymentStatus('completed');
            $payment->setValidationStatus(true);
            $this->entityManager->flush();
        }
    }

    private function handleFailedPayment($paymentIntent): void
    {
        $payment = $this->findPaymentByStripeId($paymentIntent->id);
        if ($payment) {
            $payment->setPaymentStatus('failed');
            $this->entityManager->flush();
        }
    }

    private function handleCanceledPayment($paymentIntent): void
    {
        $payment = $this->findPaymentByStripeId($paymentIntent->id);
        if ($payment) {
            $payment->setPaymentStatus('canceled');
            $this->entityManager->flush();
        }
    }

    private function findPaymentByStripeId(string $stripePaymentId): ?Payment
    {
        return $this->entityManager->getRepository(Payment::class)
            ->findOneBy(['stripePaymentId' => $stripePaymentId]);
    }
}