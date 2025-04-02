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
        
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://assets.calendly.com https://*.calendly.com https://calendly.com https://js.stripe.com https://maps.googleapis.com https://maps.gstatic.com https://api-adresse.data.gouv.fr",
            "style-src 'self' 'unsafe-inline' https://assets.calendly.com https://*.calendly.com https://fonts.googleapis.com https://cdnjs.cloudflare.com https://api-adresse.data.gouv.fr",
            "img-src 'self' data: blob: https://*.calendly.com https://maps.gstatic.com https://maps.googleapis.com https://api-adresse.data.gouv.fr",
            "font-src 'self' data: https://assets.calendly.com https://fonts.gstatic.com https://maps.googleapis.com https://cdnjs.cloudflare.com https://api-adresse.data.gouv.fr",
            "connect-src 'self' https://*.calendly.com https://calendly.com https://maps.googleapis.com https://maps.gstatic.com https://api-adresse.data.gouv.fr",
            "frame-src 'self' https://*.calendly.com https://calendly.com https://js.stripe.com https://api-adresse.data.gouv.fr",
            "media-src 'self' https://api-adresse.data.gouv.fr",
            "frame-ancestors 'self'"
        ];

        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');
        
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
    }
} 