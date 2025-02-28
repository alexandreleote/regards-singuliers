<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalendlyService
{
    private HttpClientInterface $httpClient;
    private string $calendlyAccessToken;

    public function __construct(string $calendlyAccessToken)
    {
        $this->httpClient = HttpClient::create();
        $this->calendlyAccessToken = $calendlyAccessToken;
    }

    public function createBookingEvent(Booking $booking, User $user): ?string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.calendly.com/scheduled_events', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->calendlyAccessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'start_time' => $booking->getBookingDate()->format('Y-m-d\TH:i:s\Z'),
                    'end_time' => $booking->getBookingDate()->modify('+1 hour')->format('Y-m-d\TH:i:s\Z'),
                    'invitee_email' => $user->getEmail(),
                    'event_type' => 'consultation', // Replace with actual event type
                ],
            ]);

            $data = $response->toArray();
            return $data['resource']['uri'] ?? null;
        } catch (\Exception $e) {
            // Log error or handle appropriately
            return null;
        }
    }

    public function cancelBookingEvent(string $eventLink): bool
    {
        try {
            $this->httpClient->request('DELETE', $eventLink, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->calendlyAccessToken,
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
