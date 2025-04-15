<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ContentSecurityPolicySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        
        // Gérer les requêtes OPTIONS (preflight)
        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
            $response->setContent(null);
        }
        
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://assets.calendly.com https://*.calendly.com https://calendly.com https://js.stripe.com https://maps.googleapis.com https://maps.gstatic.com https://api-adresse.data.gouv.fr https://api.stripe.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://assets.calendly.com https://*.calendly.com https://fonts.googleapis.com https://cdnjs.cloudflare.com https://api-adresse.data.gouv.fr https://cdn.jsdelivr.net",
            "img-src 'self' data: blob: https://*.calendly.com https://maps.gstatic.com https://maps.googleapis.com https://api-adresse.data.gouv.fr",
            "font-src 'self' data: https://assets.calendly.com https://fonts.gstatic.com https://maps.googleapis.com https://cdnjs.cloudflare.com https://api-adresse.data.gouv.fr",
            "connect-src 'self' wss://127.0.0.1:8000 https://127.0.0.1:8000 http://127.0.0.1:8000 https://*.calendly.com https://calendly.com https://maps.googleapis.com https://maps.gstatic.com https://api-adresse.data.gouv.fr https://api.stripe.com",
            "frame-src 'self' https://*.calendly.com https://calendly.com https://js.stripe.com https://hooks.stripe.com https://api-adresse.data.gouv.fr",
            "media-src 'self' https://api-adresse.data.gouv.fr",
            "frame-ancestors 'self'",
            "worker-src 'self' blob:",
            "manifest-src 'self'"
        ];

        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');
        
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        
        // En-têtes CORS
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-Token');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}