<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Reservation;

class CalendlyService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private string $organizationUrl;

    public function __construct(
        HttpClientInterface $client,
        string $apiKey,
        string $organizationUrl
    ) {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->organizationUrl = $organizationUrl;
    }

    /**
     * Crée un événement dans Calendly
     */
    public function createEvent(Reservation $reservation): array
    {
        $response = $this->client->request('POST', 'https://api.calendly.com/scheduled_events', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'event_type' => $this->organizationUrl,
                'invitee' => [
                    'email' => $reservation->getUser()->getEmail(),
                    'name' => $reservation->getUser()->getFullName(),
                ],
                'start_time' => $reservation->getBookedAt()->format('c'),
                'event_memberships' => [],
                'questions_and_answers' => [
                    [
                        'question' => 'Service réservé',
                        'answer' => $reservation->getService()->getTitle()
                    ]
                ]
            ],
        ]);

        return $response->toArray();
    }

    /**
     * Récupère les créneaux disponibles
     */
    public function getAvailableSlots(\DateTime $startDate, \DateTime $endDate): array
    {
        // Récupérer d'abord l'UUID de l'utilisateur
        $userResponse = $this->client->request('GET', 'https://api.calendly.com/users/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);

        $userData = $userResponse->toArray();
        $userId = $userData['resource']['uri'];

        // Ensuite, récupérer les créneaux disponibles
        $response = $this->client->request('GET', 'https://api.calendly.com/user_availability_schedules', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'query' => [
                'user' => $userId,
                'min_start_time' => $startDate->format('c'),
                'max_start_time' => $endDate->format('c'),
            ],
        ]);

        return $response->toArray();
    }

    /**
     * Annule un événement
     */
    public function cancelEvent(string $eventId): void
    {
        $this->client->request('POST', sprintf('https://api.calendly.com/scheduled_events/%s/cancellation', $eventId), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Récupère les détails d'un événement
     */
    public function getEventDetails(string $eventId): array
    {
        $response = $this->client->request('GET', sprintf('https://api.calendly.com/scheduled_events/%s', $eventId), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);

        return $response->toArray();
    }
}