<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;
use App\Service\BookingConfirmationService;
use App\Service\CalendlyService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripePaymentController extends AbstractController
{
    private $stripeSecretKey;
    private $stripeWebhookSecret;
    private $bookingRepository;
    private $bookingConfirmationService;
    private $calendlyService;

    public function __construct(
        string $stripeSecretKey,
        string $stripeWebhookSecret,
        BookingRepository $bookingRepository,
        BookingConfirmationService $bookingConfirmationService,
        CalendlyService $calendlyService
    ) {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->stripeWebhookSecret = $stripeWebhookSecret;
        $this->bookingRepository = $bookingRepository;
        $this->bookingConfirmationService = $bookingConfirmationService;
        $this->calendlyService = $calendlyService;
        Stripe::setApiKey($stripeSecretKey);
    }

    
}
