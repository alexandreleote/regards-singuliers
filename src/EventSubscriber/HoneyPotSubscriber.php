<?php

namespace App\EventSubscriber;

use App\Entity\BotIp;
use App\Repository\BotIpRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;

class HoneyPotSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $honeyPotLogger;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private BotIpRepository $botIpRepository;

    public function __construct(
        LoggerInterface $honeyPotLogger,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        BotIpRepository $botIpRepository
    )
    {
        $this->honeyPotLogger = $honeyPotLogger;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->botIpRepository = $botIpRepository;
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
        
        $data = $event->getData();
        $clientIp = $request->getClientIp();

        // Vérifier si l'IP est déjà bannie
        if ($this->botIpRepository->isIpBanned($clientIp)) {
            throw new HttpException(403, "Votre demande a été acceptée - ou pas.");
        }

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
            // Enregistrer l'IP du bot
            $botIp = new BotIp();
            $botIp->setIp($clientIp);
            $botIp->setUserAgent($request->headers->get('User-Agent'));
            $botIp->setFormType($event->getForm()->getName());

            $this->entityManager->persist($botIp);
            $this->entityManager->flush();

            $this->honeyPotLogger->info("Une potentielle tentative de robot spammeur ayant l'adresse IP suivante '{$clientIp}' a eu lieu. 
            Le champ mobilePhone contenait '{$phone}' et le champ workEmail contenait '{$work}'.");
            throw new HttpException(403, "Votre demande a été acceptée - ou pas.");
        }
    }
}