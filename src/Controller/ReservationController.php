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
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private $reservationService;
    private $reservationRepository;
    private $serviceRepository;
    private $entityManager;

    public function __construct(
        ReservationService $reservationService,
        ReservationRepository $reservationRepository,
        ServiceRepository $serviceRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->reservationService = $reservationService;
        $this->reservationRepository = $reservationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/date/{slug:service}', name: 'reservation_date')]
    public function date(Service $service): Response
    {
        
        if (!$service) {
            return $this->redirectToRoute('home');
        }

        if (!$service->isActive()) {
            throw $this->createNotFoundException('Ce service n\'est pas disponible pour la réservation.');
        }

        return $this->render('reservation/date.html.twig', [
            'page_title' => 'Choisir une date - regards singuliers',
            'meta_description' => 'Choisir une date - regards singuliers',
            'service' => $service,
            'calendly_url' => $this->getParameter('calendly.url')
        ]);
    }

    /* #[Route('/date/{id}', name: 'reservation_date')]
    public function date(Service $service): Response
    {
        if (!$service->isActive()) {
            throw $this->createNotFoundException('Ce service n\'est pas disponible pour la réservation.');
        }

        return $this->render('reservation/date.html.twig', [
            'page_title' => 'Choisir une date - regards singuliers',
            'meta_description' => 'Choisir une date - regards singuliers',
            'service' => $service,
            'calendly_url' => $this->getParameter('calendly.url')
        ]);
    } */

    #[Route('/process-date', name: 'reservation_process_date', methods: ['POST'])]
    public function processDate(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new \Exception('Données JSON invalides');
            }

            // Vérifier le token CSRF
            $token = $request->headers->get('X-CSRF-TOKEN');
            if (!$this->isCsrfTokenValid('process_date', $token)) {
                throw new InvalidCsrfTokenException('Token CSRF invalide');
            }

            // Valider les données requises
            if (!isset($data['serviceId'])) {
                throw new \Exception('ID du service manquant');
            }

            if (!isset($data['event']['event_id']) || !isset($data['event']['invitee_id'])) {
                throw new \Exception('Données de l\'événement Calendly manquantes');
            }

            $service = $this->serviceRepository->find($data['serviceId']);
            if (!$service) {
                throw new \Exception('Service non trouvé');
            }

            // Créer la réservation
            $reservation = $this->reservationService->createReservation($service, $this->getUser());
            
            // Sauvegarder les IDs Calendly
            $reservation->setCalendlyEventId($data['event']['event_id']);
            $reservation->setCalendlyInviteeId($data['event']['invitee_id']);
            
            // Définir le statut initial
            $reservation->setStatus('en attente');

            // Sauvegarder la réservation
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('reservation_payment', [
                    'slug' => $service->getSlug()
                ])
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/payment/{slug:service}', name: 'reservation_payment')]
    public function payment(Service $service): Response
    {
        $reservation = $this->reservationRepository->findOneBy([
            'user' => $this->getUser(),
            'service' => $service,
            'status' => 'en attente'
        ], ['bookedAt' => 'DESC']);

        if (!$reservation) {
            return $this->redirectToRoute('reservation_date', ['slug' => $service->getSlug()]);
        }

        $paymentData = $this->reservationService->createPaymentIntent($reservation);

        return $this->render('reservation/payment.html.twig', [
            'page_title' => 'Paiement de l\'acompte - regards singuliers',
            'meta_description' => 'Paiement de l\'acompte - regards singuliers',
            'service' => $service,
            'reservation' => $reservation,
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
            throw $this->createNotFoundException('Payment Intent non trouvé');
        }

        $reservation = $this->reservationRepository->findByStripePaymentIntentId($paymentIntentId);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation');
        }

        if ($reservation->getPayments()->isEmpty()) {
            $this->reservationService->handlePaymentSuccess($paymentIntentId);
        }

        return $this->render('reservation/success.html.twig', [
            'page_title' => 'Réservation confirmée - regards singuliers',
            'meta_description' => 'Réservation confirmée - regards singuliers',
            'reservation' => $reservation
        ]);
    }

    #[Route('/mes-reservations', name: 'mes_reservations')]
    public function myReservations(): Response
    {
        $reservations = $this->reservationRepository->findByUserWithRelations($this->getUser());

        return $this->render('reservation/mes_reservations.html.twig', [
            'page_title' => 'Mes réservations - regards singuliers',
            'meta_description' => 'Mes réservations - regards singuliers',
            'reservations' => $reservations,
        ]);
    }

    #[Route('/canceled', name: 'reservation_canceled')]
    public function canceled(): Response
    {
        return $this->render('reservation/canceled.html.twig', [
            'page_title' => 'Paiement annulé - regards singuliers',
            'meta_description' => 'Paiement annulé - regards singuliers',
        ]);
    }
}
