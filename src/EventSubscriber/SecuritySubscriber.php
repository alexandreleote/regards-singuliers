<?php

namespace App\EventSubscriber;

use App\Service\SecurityService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SecuritySubscriber implements EventSubscriberInterface
{
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Liste des routes à protéger
        $protectedRoutes = [
            '/contact',
            '/register',
            '/login',
            '/reservation',
            // Ajoutez d'autres routes sensibles ici
        ];

        $request = $event->getRequest();
        $currentPath = $request->getPathInfo();

        // Vérifie si la route actuelle doit être protégée
        $shouldProtect = false;
        foreach ($protectedRoutes as $route) {
            if (str_starts_with($currentPath, $route)) {
                $shouldProtect = true;
                break;
            }
        }

        if (!$shouldProtect) {
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
