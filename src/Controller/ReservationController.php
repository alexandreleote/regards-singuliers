<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\Reservation;
use App\Entity\Payment;
use App\Service\CalendlyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\PaymentIntent;

#[Route('/prestations')]
class ReservationController extends AbstractController
{
    #[Route('/{id}/reserver', name: 'reservation_date')]
    public function selectDate(
        Service $service,
        Request $request
    ): Response {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une réservation.');
            return $this->redirectToRoute('login');
        }

        return $this->render('reservation/select_date.html.twig', [
            'service' => $service,
            'page_title' => 'Réserver - ' . $service->getTitle(),
        ]);
    }

    #[Route('/{id}/reserver/confirmer', name: 'reservation_date_confirm', methods: ['POST'])]
    public function confirmDate(
        Service $service,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Vous devez être connecté'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $dateTime = new \DateTimeImmutable($data['datetime']);

        $reservation = new Reservation();
        $reservation->setService($service);
        $reservation->setUser($this->getUser());
        $reservation->setPrice($service->getPrice());
        $reservation->setBookedAt($dateTime);
        $reservation->setStatus('en attente de paiement');

        $em->persist($reservation);
        $em->flush();

        return $this->json([
            'success' => true,
            'redirect' => $this->generateUrl('reservation_payment', ['id' => $reservation->getId()])
        ]);
    }

    #[Route('/reservation/{id}/paiement', name: 'reservation_payment')]
    public function payment(
        Reservation $reservation,
        Request $request
    ): Response {
        if ($this->getUser() !== $reservation->getUser()) {
            throw $this->createAccessDeniedException();
        }

        try {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($reservation->getPrice() * 100),
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'reservation_id' => $reservation->getId()
                ],
            ]);

            return $this->render('reservation/payment.html.twig', [
                'reservation' => $reservation,
                'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
                'client_secret' => $paymentIntent->client_secret,
                'page_title' => 'Paiement - ' . $reservation->getService()->getTitle(),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'initialisation du paiement.');
            return $this->redirectToRoute('prestations');
        }
    }

    #[Route('/reservation/success', name: 'reservation_success')]
    public function success(): Response
    {
        return $this->render('reservation/success.html.twig', [
            'page_title' => 'Réservation confirmée',
        ]);
    }

    #[Route('/reservation/cancel', name: 'reservation_cancel')]
    public function cancel(): Response
    {
        return $this->render('reservation/cancel.html.twig', [
            'page_title' => 'Réservation annulée',
        ]);
    }
}