<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class SecurityHeadersListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        
        // Set Content Security Policy
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google-analytics.com https://www.googletagmanager.com https://www.gstatic.com https://www.recaptcha.net https://cdn.segment.io https://connect.facebook.net https://notifier-configs.airbrake.io https://*.datadoghq.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: https: http:",
            "font-src 'self' https://fonts.gstatic.com",
            "frame-src 'self' https://www.recaptcha.net https://calendly.com",
            "connect-src 'self' https://api.calendly.com https://*.airbrake.io https://*.segment.io https://*.facebook.com https://*.google-analytics.com https://*.datadoghq.com",
            "worker-src 'self' blob: https://www.recaptcha.net"
        ];
        
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        
        // Set other security headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
} 