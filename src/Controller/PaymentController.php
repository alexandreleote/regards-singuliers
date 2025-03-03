<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    private $stripeSecretKey;
    private $stripePublicKey;
    private $serviceRepository;

    public function __construct(
        string $stripeSecretKey, 
        string $stripePublicKey,
        ServiceRepository $serviceRepository
    ) {
        $this->stripeSecretKey = $stripeSecretKey;
        $this->stripePublicKey = $stripePublicKey;
        $this->serviceRepository = $serviceRepository;
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/create-checkout-session/{serviceId}', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, int $serviceId): Response
    {
        $service = $this->serviceRepository->find($serviceId);

        if (!$service) {
            throw $this->createNotFoundException('Service not found');
        }

        try {
            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $service->getPrice() * 100, // En centimes
                        'product_data' => [
                            'name' => $service->getTitle(),
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('booking_success', ['serviceId' => $serviceId], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('booking_cancel', ['serviceId' => $serviceId], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($checkout_session->url);
        } catch (\Exception $e) {
            // Gérer les erreurs de paiement
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/booking-success/{serviceId}', name: 'booking_success')]
    public function bookingSuccess(int $serviceId): Response
    {
        $service = $this->serviceRepository->find($serviceId);
        
        return $this->render('payment/success.html.twig', [
            'service' => $service,
            'calendly_link' => $service->getCalendlyLink(),
            'stripe_public_key' => $this->stripePublicKey
        ]);
    }

    #[Route('/booking-cancel/{serviceId}', name: 'booking_cancel')]
    public function bookingCancel(int $serviceId): Response
    {
        $service = $this->serviceRepository->find($serviceId);

        return $this->render('payment/cancel.html.twig', [
            'serviceId' => $serviceId,
            'service' => $service
        ]);
    }
}
