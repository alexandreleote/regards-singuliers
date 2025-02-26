<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class AccessDeniedListener
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // Check if the exception is an AccessDeniedException or a 403 HTTP exception
        if ($exception instanceof AccessDeniedException || 
            ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403)) {
            
            $request = $event->getRequest();
            $session = $request->getSession();

            // Add flash message
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('error', 'Vous n\'avez pas les autorisations nécessaires pour accéder à cette page.');
            }

            // Create a redirect response to home page
            $response = new RedirectResponse($this->router->generate('home'));
            
            // Set the response and stop further exception processing
            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}
