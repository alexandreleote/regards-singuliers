<?php

namespace App\EventSubscriber;

use App\Entity\BotIp;
use App\Service\SecurityService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class HoneyPotSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $honeyPotLogger;
    private RequestStack $requestStack;
    private SecurityService $securityService;
    private const MIN_FORM_TIME = 2; // Temps minimum en secondes pour remplir le formulaire
    private const MAX_FORM_TIME = 3600; // Temps maximum en secondes (1 heure)
    private const HONEYPOT_FIELD_CLASS = 'honeypot-field'; // Classe CSS des champs honeypot

    public function __construct(
        LoggerInterface $honeyPotLogger,
        RequestStack $requestStack,
        SecurityService $securityService
    ) {
        $this->honeyPotLogger = $honeyPotLogger;
        $this->requestStack = $requestStack;
        $this->securityService = $securityService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
            FormEvents::PRE_SUBMIT => 'checkHoneyJar'
        ];
    }

    public function checkHoneyJar(FormEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if(!$request) {
            return;
        }
        
        $data = $event->getData();
        $clientIp = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent', '');

        // Vérifier si l'IP est bloquée
        if ($this->securityService->isIpBlocked()) {
            $this->logAndThrowException($clientIp, $userAgent, 'IP already blocked');
        }

        // Vérification du temps de remplissage
        if (!isset($data['_timestamp'])) {
            $this->logAndThrowException($clientIp, $userAgent, 'Missing timestamp');
        }

        $submissionTime = time() - (int)$data['_timestamp'];
        if ($submissionTime < self::MIN_FORM_TIME || $submissionTime > self::MAX_FORM_TIME) {
            $this->logAndThrowException($clientIp, $userAgent, "Invalid submission time: {$submissionTime}s");
        }

        // Vérifier tous les champs honeypot dans les données
        foreach ($data as $fieldName => $value) {
            // Si le champ commence par 'honeypot_' ou contient la classe honeypot
            if (str_starts_with($fieldName, 'honeypot_') || 
                (isset($data['class']) && str_contains($data['class'], self::HONEYPOT_FIELD_CLASS))) {
                
                $value = trim($value);
                
                // Protection contre les injections
                if ($this->containsSuspiciousContent($value)) {
                    $this->logAndThrowException($clientIp, $userAgent, 'Suspicious content detected');
                }

                // Si le champ n'est pas vide, c'est un bot
                if ($value !== '') {
                    $this->logAndThrowException($clientIp, $userAgent, "Honeypot field '{$fieldName}' not empty");
                }
            }
        }
    }

    private function containsSuspiciousContent(string $value): bool
    {
        // Vérifie les caractères suspects et les motifs d'injection
        $suspiciousPatterns = [
            '/[<>]/', // Tags HTML
            '/\\x[0-9a-f]{2}/i', // Caractères hexadécimaux
            '/\\u[0-9a-f]{4}/i', // Unicode escape sequences
            '/\\n|\\r|\\t/', // Caractères d'échappement
            '/[\x00-\x1F\x7F]/', // Caractères de contrôle
            '/\b(SELECT|INSERT|UPDATE|DELETE|UNION|DROP|OR|AND)\b/i', // Mots-clés SQL
            '/(javascript|vbscript):/i', // Scripts
            '/on\w+\s*=/', // Event handlers
            '/data:\s*\w+\/\w+;/', // Data URIs
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Vérifie si la route doit être protégée
        if (!$this->securityService->shouldProtectRoute($path)) {
            return;
        }

        // Si l'IP est bloquée
        if ($this->securityService->isIpBlocked()) {
            $blockInfo = $this->securityService->getBlockReason();
            
            $response = $request->isXmlHttpRequest()
                ? new JsonResponse($blockInfo, Response::HTTP_FORBIDDEN)
                : new Response(
                    $this->getBlockedHtml($blockInfo),
                    Response::HTTP_FORBIDDEN,
                    ['Content-Type' => 'text/html']
                );

            $event->setResponse($response);
        }
    }

    private function logAndThrowException(string $clientIp, string $userAgent, string $reason): void
    {
        // Détecter le type de formulaire à partir de la requête
        $request = $this->requestStack->getCurrentRequest();
        $path = $request ? $request->getPathInfo() : '';
        $formType = match (true) {
            str_contains($path, '/contact') => 'contact_form',
            str_contains($path, '/inscription') => 'registration_form',
            default => 'unknown_form'
        };

        $this->honeyPotLogger->warning('Bot detected', [
            'ip' => $clientIp,
            'user_agent' => $userAgent,
            'form_type' => $formType,
            'reason' => $reason,
            'path' => $path
        ]);

        // Bloquer l'IP
        $this->securityService->blockIp($formType, $reason);

        throw new HttpException(Response::HTTP_FORBIDDEN, 'Access Denied');
    }

    private function getBlockedHtml(array $blockInfo): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Accès Restreint</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .title { color: #d32f2f; }
                .info { background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 15px 0; }
                .timestamp { color: #666; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1 class="title">Accès Restreint</h1>
                <p>{$blockInfo['reason']}</p>
                <div class="info">
                    <p>Type de formulaire : {$blockInfo['form_type']}</p>
                    <p class="timestamp">Détecté le : {$blockInfo['detected_at']}</p>
                    <p class="timestamp">Restriction active jusqu'au : {$blockInfo['expires_at']}</p>
                </div>
                <p>Si vous pensez qu'il s'agit d'une erreur, veuillez nous contacter par un autre moyen.</p>
            </div>
        </body>
        </html>
        HTML;
    }
}