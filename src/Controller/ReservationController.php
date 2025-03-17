<?php

namespace App\Controller;

use App\Entity\Service;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    #[Route('/payment/{id}', name: 'reservation_payment')]
    public function payment(Service $service): Response
    {
        $reservation = $this->reservationService->createReservation($service, $this->getUser());
        $paymentData = $this->reservationService->createPaymentIntent($reservation);

        return $this->render('reservation/payment.html.twig', [
            'page_title' => 'Paiement de l\'acompte',
            'service' => $service,
            'deposit_amount' => $paymentData['depositAmount'],
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
            'client_secret' => $paymentData['clientSecret'],
        ]);
    }

    #[Route('/success', name: 'reservation_success')]
    public function success(Request $request): Response
    {
        $paymentIntentId = $request->query->get('payment_intent');
        
        if (!$paymentIntentId) {
            return $this->redirectToRoute('reservation_canceled');
        }

        try {
            $reservation = $this->reservationService->handlePaymentSuccess($paymentIntentId);
            
            return $this->render('reservation/success.html.twig', [
                'page_title' => 'Paiement réussi',
                'service' => $reservation->getService(),
                'deposit_amount' => $reservation->getPrice() * 0.5,
            ]);
        } catch (\Exception $e) {
            return $this->redirectToRoute('reservation_canceled');
        }
    }

    #[Route('/canceled', name: 'reservation_canceled')]
    public function canceled(): Response
    {
        return $this->render('reservation/canceled.html.twig', [
            'page_title' => 'Paiement annulé',
        ]);
    }

    #[Route('/date/{id}', name: 'reservation_date')]
    public function date(Service $service): Response
    {
        return $this->render('reservation/date.html.twig', [
            'page_title' => 'Choisir une date',
            'service' => $service,
            'calendly_url' => $this->getParameter('calendly.url'),
        ]);
    }
}
