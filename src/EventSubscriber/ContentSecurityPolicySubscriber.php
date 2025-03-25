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
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://assets.calendly.com https://*.calendly.com https://calendly.com https://js.stripe.com",
            "style-src 'self' 'unsafe-inline' https://assets.calendly.com https://*.calendly.com https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https://*.calendly.com",
            "font-src 'self' data: https://assets.calendly.com https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "connect-src 'self' https://*.calendly.com https://calendly.com",
            "frame-src 'self' https://*.calendly.com https://calendly.com https://js.stripe.com",
            "media-src 'self'",
            "frame-ancestors 'self'"
        ];

        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');
        
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
    }
} 