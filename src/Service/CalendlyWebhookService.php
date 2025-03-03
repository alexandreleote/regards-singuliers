<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

class CalendlyWebhookService
{
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, 
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    public function handleWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        // Validate webhook signature
        if (!$this->validateWebhookSignature($request)) {
            $this->logger->warning('Invalid Calendly webhook signature');
            return new Response('Invalid signature', Response::HTTP_FORBIDDEN);
        }

        // Process different Calendly event types
        switch ($payload['event']) {
            case 'invitee.created':
                return $this->handleBookingCreation($payload);
            case 'invitee.canceled':
                return $this->handleBookingCancellation($payload);
            default:
                $this->logger->info('Unhandled Calendly event', ['event' => $payload['event']]);
                return new Response('Unhandled event', Response::HTTP_OK);
        }
    }

    private function handleBookingCreation(array $payload): Response
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                $this->logger->warning('Unauthenticated Calendly webhook request');
                return new Response('User not authenticated', Response::HTTP_UNAUTHORIZED);
            }

            $service = $this->findServiceByCalendlyLink($payload['payload']['event']['uri']);
            if (!$service) {
                $this->logger->error('Service not found for Calendly event', [
                    'event_uri' => $payload['payload']['event']['uri']
                ]);
                return new Response('Service not found', Response::HTTP_NOT_FOUND);
            }

            $booking = new Booking();
            $booking->setUser($user)
                ->setService($service)
                ->setStartTime(new \DateTime($payload['payload']['event']['start_time']))
                ->setEndTime(new \DateTime($payload['payload']['event']['end_time']))
                ->setCalendlyEventId($payload['payload']['event']['uuid'])
                ->setCalendlyEventLink($payload['payload']['event']['uri'])
                ->setStatus(Booking::STATUS_CONFIRMED);

            $this->entityManager->persist($booking);
            $this->entityManager->flush();

            $this->logger->info('Booking created from Calendly webhook', [
                'booking_id' => $booking->getId(),
                'service' => $service->getTitle()
            ]);

            return new Response('Booking created', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Error processing Calendly booking', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return new Response('Error processing booking: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleBookingCancellation(array $payload): Response
    {
        $calendlyEventId = $payload['payload']['event']['uuid'];
        $booking = $this->entityManager->getRepository(Booking::class)
            ->findOneBy(['calendlyEventId' => $calendlyEventId]);

        if ($booking) {
            $booking->setStatus(Booking::STATUS_CANCELLED);
            $this->entityManager->flush();

            $this->logger->info('Booking canceled from Calendly webhook', [
                'booking_id' => $booking->getId(),
                'calendly_event_id' => $calendlyEventId
            ]);
        } else {
            $this->logger->warning('Booking not found for cancellation', [
                'calendly_event_id' => $calendlyEventId
            ]);
        }

        return new Response('Booking canceled', Response::HTTP_OK);
    }

    private function findServiceByCalendlyLink(string $eventUri): ?Service
    {
        return $this->entityManager->getRepository(Service::class)
            ->findOneBy(['calendlyLink' => $eventUri]);
    }

    private function validateWebhookSignature(Request $request): bool
    {
        // TODO: Implement actual Calendly webhook signature validation
        // This is a placeholder - you'll need to implement proper signature validation
        $this->logger->warning('Webhook signature validation is currently disabled');
        return true;
    }

    private function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        
        if ($token && $token->getUser() instanceof User) {
            return $token->getUser();
        }

        return null;
    }
}
