<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendlyWebhookController extends AbstractController
{
    public function __construct(
        private ReservationRepository $reservationRepository
    ) {
    }

    #[Route('/webhook/calendly', name: 'calendly_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        
        // Vérifier que c'est bien un événement de création de rendez-vous
        if ($payload['event'] !== 'invitee.created') {
            return new Response('', 200);
        }

        // Récupérer l'email de l'utilisateur qui a pris le rendez-vous
        $email = $payload['payload']['invitee']['email'];
        
        // Trouver la dernière réservation en attente pour cet utilisateur
        $reservation = $this->reservationRepository->findOneBy([
            'user' => $this->getUser(),
            'status' => 'en attente'
        ], ['bookedAt' => 'DESC']);

        if ($reservation) {
            // Mettre à jour le statut de la réservation
            $reservation->setStatus('confirmed');
            $this->reservationRepository->save($reservation, true);
        }

        return new Response('', 200);
    }

    #[Route('/reservation/confirmation', name: 'reservation_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('reservation/confirmation.html.twig', [
            'page_title' => 'Réservation confirmée - regards singuliers',
            'meta_description' => 'Votre projet prend forme ! Confirmation de votre rendez-vous avec notre architecte d\'intérieur. Tous les détails de votre future transformation sont désormais enregistrés.', 
        ]);
    }
} 