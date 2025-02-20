<?php

namespace App\Service;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalendlyService
{
    private string $apiKey;
    private string $webhookSigningKey;
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        string $calendlyApiKey,
        string $calendlyWebhookSigningKey
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->apiKey = $calendlyApiKey;
        $this->webhookSigningKey = $calendlyWebhookSigningKey;
    }

    public function syncAppointments(): array
    {
        $response = $this->httpClient->request('GET', 'https://api.calendly.com/scheduled_events', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'query' => [
                'min_start_time' => (new \DateTime('today'))->format('c'),
                'status' => 'active',
                'count' => 100
            ]
        ]);

        $data = $response->toArray();
        $appointments = [];

        foreach ($data['collection'] as $event) {
            // Récupérer les détails de l'invité
            $inviteeResponse = $this->httpClient->request('GET', $event['uri'] . '/invitees', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ]
            ]);

            $inviteeData = $inviteeResponse->toArray();
            if (empty($inviteeData['collection'])) {
                continue;
            }

            $invitee = $inviteeData['collection'][0];

            // Vérifier si le rendez-vous existe déjà
            $existingAppointment = $this->entityManager->getRepository(Appointment::class)
                ->findOneBy(['calendlyEventUri' => $event['uri']]);

            if (!$existingAppointment) {
                $appointment = new Appointment();
                $appointment->setName($invitee['name']);
                $appointment->setEmail($invitee['email']);
                $appointment->setStartTime(new \DateTime($event['start_time']));
                $appointment->setEndTime(new \DateTime($event['end_time']));
                $appointment->setCalendlyEventUri($event['uri']);
                $appointment->setStatus('scheduled');

                if (isset($invitee['questions_and_answers'])) {
                    $notes = array_map(function($qa) {
                        return sprintf("%s: %s", $qa['question'], $qa['answer']);
                    }, $invitee['questions_and_answers']);
                    $appointment->setNotes(implode("\n", $notes));
                }

                $this->entityManager->persist($appointment);
                $appointments[] = $appointment;
            }
        }

        $this->entityManager->flush();
        return $appointments;
    }

    public function handleWebhook(array $payload, string $signature): void
    {
        if (!$this->verifyWebhookSignature($signature, json_encode($payload))) {
            throw new \Exception('Invalid webhook signature');
        }

        $event = $payload['event'];
        
        if ($event['type'] !== 'invitee.created' && $event['type'] !== 'invitee.canceled') {
            return;
        }

        $invitee = $event['payload']['invitee'];
        $eventData = $event['payload']['event'];

        $appointment = new Appointment();
        $appointment->setName($invitee['name']);
        $appointment->setEmail($invitee['email']);
        $appointment->setStartTime(new \DateTime($eventData['start_time']));
        $appointment->setEndTime(new \DateTime($eventData['end_time']));
        $appointment->setCalendlyEventUri($eventData['uri']);
        $appointment->setStatus($event['type'] === 'invitee.created' ? 'scheduled' : 'canceled');
        
        if (isset($invitee['questions_and_answers'])) {
            $notes = array_map(function($qa) {
                return sprintf("%s: %s", $qa['question'], $qa['answer']);
            }, $invitee['questions_and_answers']);
            $appointment->setNotes(implode("\n", $notes));
        }

        $this->entityManager->persist($appointment);
        $this->entityManager->flush();
    }

    private function verifyWebhookSignature(string $signature, string $payload): bool
    {
        $hmac = hash_hmac('sha256', $payload, $this->webhookSigningKey);
        return hash_equals($signature, $hmac);
    }

    public function getScheduledEvents(): array
    {
        $response = $this->httpClient->request('GET', 'https://api.calendly.com/scheduled_events', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'query' => [
                'status' => 'active',
                'count' => 100
            ]
        ]);

        return $response->toArray();
    }
}
