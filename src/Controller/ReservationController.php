<?php

namespace App\Controller;

use App\Entity\Service;
use App\Service\ReservationService;
use App\Repository\ReservationRepository;
use App\Repository\ServiceRepository;
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
    private $reservationRepository;
    private $serviceRepository;

    public function __construct(
        ReservationService $reservationService,
        ReservationRepository $reservationRepository,
        ServiceRepository $serviceRepository
    ) {
        $this->reservationService = $reservationService;
        $this->reservationRepository = $reservationRepository;
        $this->serviceRepository = $serviceRepository;
    }

    #[Route('/payment/{id}', name: 'reservation_payment')]
    public function payment(Service $service): Response
    {
        // Vérifier que le service est actif
        if (!$service->isActive()) {
            throw $this->createNotFoundException('Ce service n\'est pas disponible pour la réservation.');
        }

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
            throw $this->createNotFoundException('Paramètre payment_intent manquant');
        }

        $reservation = $this->reservationRepository->findByStripePaymentIntentId($paymentIntentId);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        // Vérifier que l'utilisateur est bien le propriétaire de la réservation
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation');
        }

        // Création temporaire du paiement si non existant
        if ($reservation->getPayments()->isEmpty()) {
            $this->reservationService->handlePaymentSuccess($paymentIntentId);
        }

        return $this->render('reservation/success.html.twig', [
            'page_title' => 'Réservation confirmée',
            'reservation' => $reservation,
        ]);
    }

    #[Route('/mes-reservations', name: 'mes_reservations')]
    public function myReservations(): Response
    {
        $reservations = $this->reservationRepository->findByUserWithRelations($this->getUser());

        return $this->render('reservation/mes_reservations.html.twig', [
            'page_title' => 'Mes réservations',
            'reservations' => $reservations,
        ]);
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
        // Récupérer la dernière réservation non confirmée de l'utilisateur pour ce service
        $reservation = $this->reservationRepository->findOneBy([
            'user' => $this->getUser(),
            'service' => $service,
            'status' => 'en attente'
        ], ['bookedAt' => 'DESC']);

        return $this->render('reservation/date.html.twig', [
            'page_title' => 'Choisir une date',
            'service' => $service,
            'calendly_url' => $this->getParameter('calendly.url'),
            'reservation' => $reservation,
        ]);
    }
}
