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
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://assets.calendly.com https://cdn.segment.io https://connect.facebook.net https://*.googleapis.com https://*.gstatic.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://assets.calendly.com https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "img-src 'self' data: https: blob: https://*.googleapis.com https://*.gstatic.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "connect-src 'self' https://api.stripe.com https://api.calendly.com https://*.googleapis.com https://*.gstatic.com",
            "frame-src 'self' https://js.stripe.com https://assets.calendly.com https://www.facebook.com",
            "media-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
    }
} 