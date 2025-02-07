<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\Reservation;
use App\Service\StripeService;
use App\Form\ReservationDateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ReservationController extends AbstractController
{
    #[Route('/reservation/service/{id}', name: 'app_reservation_start')]
    public function start(Service $service, Request $request, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = new Reservation();
        $reservation->setService($service);
        $reservation->setUser($this->getUser());
        $reservation->setPrice($service->getPrice());
        $reservation->setStatus('pending');

        $form = $this->createForm(ReservationDateType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
        
            return $this->redirectToRoute('app_reservation_payment', [
                'id' => $reservation->getId(),
            ]);
        }

        return $this->render('reservation/index.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/{id}/payment', name: 'app_reservation_payment')]
    public function payment(Reservation $reservation, StripeService $stripeService): Response
    {
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $paymentIntent = $stripeService->createPaymentIntent($reservation);

        return $this->render('reservation/payment.html.twig', [
            'reservation' => $reservation,
            'clientSecret' => $paymentIntent->client_secret,
            'publicKey' => $this->getParameter('app.stripe_public_key'),
        ]);
    }
}
