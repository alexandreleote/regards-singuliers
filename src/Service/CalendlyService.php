<?php

namespace App\Service;

use App\Entity\Reservation;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalendlyService
{
    private $client;
    private $apiKey;
    private $organizationUrl;

    public function __construct(
        HttpClientInterface $client,
        string $apiKey,
        string $organizationUrl
    ) {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->organizationUrl = $organizationUrl;
    }

    public function createEvent(Reservation $reservation): void
    {
        $response = $this->client->request('POST', 'https://api.calendly.com/scheduled_events', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'start_time' => $reservation->getBookedAt()->format('Y-m-d\TH:i:s\Z'),
                'end_time' => $reservation->getBookedAt()->modify('+30 minutes')->format('Y-m-d\TH:i:s\Z'),
                'event_type' => 'https://api.calendly.com/event_types/' . $this->organizationUrl,
                'location' => [
                    'type' => 'custom',
                    'location' => 'En ligne',
                ],
                'invitees_counter' => true,
                'name' => $reservation->getUser()->getEmail(),
                'email' => $reservation->getUser()->getEmail(),
                'custom_data' => [
                    'reservation_id' => $reservation->getId(),
                    'service_title' => $reservation->getService()->getTitle(),
                ],
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \RuntimeException('Erreur lors de la création de l\'événement Calendly');
        }
    }
} 