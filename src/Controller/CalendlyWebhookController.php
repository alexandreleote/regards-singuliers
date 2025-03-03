<?php

namespace App\Controller;

use App\Service\CalendlyWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendlyWebhookController extends AbstractController
{
    private CalendlyWebhookService $calendlyWebhookService;
    private LoggerInterface $logger;

    public function __construct(
        CalendlyWebhookService $calendlyWebhookService, 
        LoggerInterface $logger
    ) {
        $this->calendlyWebhookService = $calendlyWebhookService;
        $this->logger = $logger;
    }

    #[Route('/webhooks/calendly', name: 'calendly_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        // Log the raw payload for debugging
        $payload = $request->getContent();
        $this->logger->info('Received Calendly Webhook', [
            'payload' => $payload,
            'headers' => $request->headers->all()
        ]);

        // Basic webhook authentication (replace with your actual secret)
        $webhookSecret = $this->getParameter('calendly_webhook_secret');
        $providedSecret = $request->headers->get('X-Calendly-Signature');

        if (!$this->validateWebhookSignature($payload, $providedSecret, $webhookSecret)) {
            $this->logger->warning('Invalid Calendly webhook signature');
            return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->calendlyWebhookService->handleWebhook($request);
        } catch (\Exception $e) {
            $this->logger->error('Calendly Webhook Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new Response('Internal Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateWebhookSignature(string $payload, ?string $providedSignature, string $secret): bool
    {
        // Implement your signature validation logic here
        // This is a placeholder - replace with actual Calendly webhook signature verification
        return $providedSignature !== null && hash_hmac('sha256', $payload, $secret) === $providedSignature;
    }
}
