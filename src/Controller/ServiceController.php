<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\Reservation;
use App\Service\StripeService;
use App\Form\ReservationDateType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ServiceController extends AbstractController
{
    #[Route('/services', name: 'services_list')]
    public function index(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findServices();

        return $this->render('service/index.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/services/{id}', name: 'service_detail')]
    public function show(Service $service): Response
    {
        return $this->render('service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/service/{id}/reserver', name: 'app_reservation_create')]
    public function create(
        Service $service,
        Request $request,
        EntityManagerInterface $entityManager,
        StripeService $stripeService
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une réservation');
            return $this->redirectToRoute('app_login');
        }

        // Créer le formulaire de réservation
        $reservation = new Reservation();
        $reservation->setService($service);
        $reservation->setUser($user);
        $reservation->setStatus('pending');
        $reservation->setPrice($service->getPrice());
        
        $form = $this->createForm(ReservationDateType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Démarrer une transaction
                $entityManager->beginTransaction();
                
                // Persister la réservation
                $entityManager->persist($reservation);
                $entityManager->flush();

                // Créer l'intention de paiement
                $paymentIntent = $stripeService->createPaymentIntent($reservation);

                // Si tout va bien, valider la transaction
                $entityManager->commit();

                return $this->redirectToRoute('app_reservation_payment', [
                    'id' => $reservation->getId()
                ]);
            } catch (\Exception $e) {
                // En cas d'erreur, annuler la transaction
                $entityManager->rollback();
                $this->addFlash('error', 'Une erreur est survenue lors de la création de votre réservation : ' . $e->getMessage());
                return $this->redirectToRoute('services_list');
            }
        }

        return $this->render('reservation/create.html.twig', [
            'service' => $service,
            'form' => $form->createView()
        ]);
    }
}
