<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Service;
use App\Form\BookingType;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ServiceController extends AbstractController
{
    #[Route('/admin/prestations', name: 'admin_services')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('service/adminIndex.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/admin/prestation/ajouter', name: 'admin_service_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Service créé avec succès');
            return $this->redirectToRoute('admin_services');
        }

        return $this->render('service/new_edit.html.twig', [
            'service' => $service,
            'form' => $form,
            'editing' => false
        ]);
    }

    #[Route('/admin/prestation/{slug}/editer', name: 'admin_service_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request, 
        string $slug, 
        ServiceRepository $serviceRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $service = $serviceRepository->findOneBySlug($slug);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Service modifié avec succès');
            return $this->redirectToRoute('admin_services');
        }

        return $this->render('service/new_edit.html.twig', [
            'service' => $service,
            'form' => $form,
            'editing' => true
        ]);
    }

    #[Route('/prestations', name: 'prestations', methods: ['GET'])]
    public function index(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('service/index.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/prestation/{slug}/reserver', name: 'prestation_reservation')]
    public function book(
        Request $request,
        string $slug,
        ServiceRepository $serviceRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        // Ensure only authenticated users can book
        $this->denyAccessUnlessGranted('ROLE_USER');

        $service = $serviceRepository->findOneBySlug($slug);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }

        $booking = new Booking();
        $booking->setService($service);
        $booking->setUser($this->getUser());
        $booking->setStatus(Booking::STATUS_PENDING);
        $booking->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(BookingType::class, $booking, [
            'service' => $service
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($booking);
                $entityManager->flush();

                $logger->info('Réservation créée', [
                    'reservationId' => $booking->getId(),
                    'service' => $service->getTitle(),
                    'user' => $this->getUser()->getEmail()
                ]);

                return $this->redirectToRoute('prestation_paiement', [
                    'reservationId' => $booking->getId()
                ]);
            } catch (\Exception $e) {
                $logger->error('Erreur lors de la création de la réservation', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->addFlash('error', 'Une erreur est survenue lors de la création de la réservation');
                return $this->redirectToRoute('prestations');
            }
        }

        return $this->render('service/book.html.twig', [
            'service' => $service,
            'form' => $form->createView()
        ]);
    }
}
