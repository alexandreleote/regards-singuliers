<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookingConfirmationService
{
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $httpClient;
    private string $calendlyAccessToken;
    private string $calendlyOrganizationUri;

    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient,
        string $calendlyAccessToken,
        string $calendlyOrganizationUri
    ) {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->calendlyAccessToken = $calendlyAccessToken;
        $this->calendlyOrganizationUri = $calendlyOrganizationUri;
    }

    public function confirmBookingAfterPayment(Booking $booking, Session $stripeSession): bool
    {
        try {
            // 1. Vérifier le paiement Stripe
            if ($stripeSession->payment_status !== 'paid') {
                return false;
            }

            // 2. Mettre à jour le statut de la réservation
            $booking->setStatus(Booking::STATUS_CONFIRMED);
            $this->entityManager->persist($booking);

            // 3. Bloquer le créneau dans Calendly
            $this->blockCalendlySlot($booking);

            $this->entityManager->flush();
            return true;

        } catch (\Exception $e) {
            // Gérer les erreurs de confirmation
            // Vous pouvez ajouter du logging ici
            return false;
        }
    }

    private function blockCalendlySlot(Booking $booking): void
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.calendly.com/scheduling_constraints', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->calendlyAccessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'start_time' => $booking->getBookingDate()->format('c'),
                    'end_time' => $booking->getBookingDate()->modify('+1 hour')->format('c'),
                    'type' => 'busy_time',
                    'organization_uri' => $this->calendlyOrganizationUri
                ]
            ]);

            // Gérer la réponse de Calendly si nécessaire
        } catch (\Exception $e) {
            // Gérer les erreurs d'API Calendly
        }
    }
}
