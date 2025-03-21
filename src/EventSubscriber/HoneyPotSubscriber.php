<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HoneyPotSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $honeyPotLogger;

    private RequestStack $requestStack;

    public function __construct(
        LoggerInterface $honeyPotLogger,
        RequestStack $requestStack
    )
    {
        $this->honeyPotLogger = $honeyPotLogger;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'checkHoneyJar'
        ];
    }

    public function checkHoneyJar(FormEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if(!$request) {
            return;
        }

        // dd($event);      
        
        $data = $event->getData();

        if (!array_key_exists('mobilePhone', $data) || !array_key_exists('workEmail', $data))
        {
            throw new HttpException(400, "Don't touch the form please!");
        }
    
        // Destructuring des valeurs du form
        [
            'mobilePhone'   => $phone,
            'workEmail'     => $work
        ] = $data;

        // Si les valeurs ne sont pas vides, on a affaire à un bot
        if ($phone !== "" || $work !== "") {
            $this->honeyPotLogger->info("Une potentielle tentative de robot spammeur ayant l'adresse IP suivante '{$request->getClientIp()}' a eu lieu. 
            Le champ mobilePhone contenait '{$phone}' et le champ workEmail contenait '{$work}'.");
            throw new HttpException(403, "Votre demande a été acceptée - ou pas.");
        }
    }
}