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
                'end_time' => $reservation->getBookedAt()->format('Y-m-d\TH:i:s\Z'),
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

    public function getEventDetails(string $eventId): array
    {
        try {
            $response = $this->client->request('GET', "https://api.calendly.com/scheduled_events/{$eventId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Erreur lors de la récupération des détails de l\'évènement Calendly');
            }

            $data = $response->toArray();
            
            // Vérifier si la réponse contient les données nécessaires
            if (!isset($data['resource'])) {
                throw new \RuntimeException('Format de réponse Calendly invalide');
            }

            return $data;

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Erreur Calendly: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la récupération des détails de l\'évènement Calendly: ' . $e->getMessage());
        }
    }

    /**
     * Annule un événement Calendly
     */
    public function cancelEvent(string $eventId): bool
    {
        try {
            // Récupérer d'abord les informations sur l'événement pour obtenir l'URI de l'invité
            $eventDetails = $this->getEventDetails($eventId);
            
            if (!$eventDetails || !isset($eventDetails['resource']['uri'])) {
                throw new \Exception('Impossible de récupérer les détails de l\'événement Calendly');
            }
            
            // Construire l'URL pour annuler
            $cancelUrl = 'https://api.calendly.com/scheduled_events/' . $eventId . '/cancellation';
            
            // Envoyer la requête d'annulation
            $response = $this->client->request('POST', $cancelUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'reason' => 'Annulation par l\'utilisateur'
                ]
            ]);
            
            return $response->getStatusCode() === 201 || $response->getStatusCode() === 204;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de l\'annulation sur Calendly: ' . $e->getMessage());
        }
    }
}
