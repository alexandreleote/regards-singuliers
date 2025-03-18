<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class SecurityHeadersListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        
        // Définir la politique CSP
        $cspHeader = "default-src * 'unsafe-inline' 'unsafe-eval' data: blob:; " .
            "frame-ancestors 'self'";

        $cspHeader = "default-src 'self' https://calendly.com https://assets.calendly.com; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://assets.calendly.com https://calendly.com; " .
            "style-src 'self' 'unsafe-inline' https://assets.calendly.com https://calendly.com; " .
            "frame-src 'self' https://js.stripe.com https://assets.calendly.com https://calendly.com https://www.facebook.com; " .
            "img-src 'self' data: https: https://assets.calendly.com https://calendly.com; " .
            "connect-src 'self' https://api.stripe.com https://calendly.com https://assets.calendly.com wss://router.calendly.com; " .
            "font-src 'self' data: https://assets.calendly.com; " .
            "object-src 'none'; " .
            "media-src 'self'; " .
            "form-action 'self'; " .
            "frame-ancestors 'self'";

        $response->headers->set('Content-Security-Policy', $cspHeader);
    }
} 