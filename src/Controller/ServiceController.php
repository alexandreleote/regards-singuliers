<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Service;
use App\Form\BookingType;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ServiceController extends AbstractController
{
    #[Route('/admin/prestations', name: 'admin_services', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('service/adminIndex.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/admin/prestation/ajouter', name: 'admin_service_new', methods: ['GET', 'POST'])]
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
            return $this->redirectToRoute('admin');
        }

        return $this->render('service/new.html.twig', [
            'service' => $service,
            'form' => $form,
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

    #[Route('/prestation/{slug}/reserver', name: 'prestation_reservation', methods: ['GET', 'POST'])]
    public function book(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        // Ensure only authenticated users can book
        $this->denyAccessUnlessGranted('ROLE_USER');

        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->setService($service);
            $booking->setUser($this->getUser());
            $booking->setStatus('pending');

            $entityManager->persist($booking);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée');
            return $this->redirectToRoute('prestations');
        }

        return $this->render('service/book.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{path}', name: 'app_404', requirements: ['path' => '.+'], methods: ['GET'])]
    public function notFound(): Response
    {
        return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }
}
