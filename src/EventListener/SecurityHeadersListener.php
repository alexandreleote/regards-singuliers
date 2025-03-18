<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class SecurityHeadersListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        // Temporairement désactivé pour éviter les conflits avec ContentSecurityPolicySubscriber
        return;
    }
} 