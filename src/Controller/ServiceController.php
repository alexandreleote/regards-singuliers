<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ServiceController extends AbstractController
{
    #[Route('/prestations', name: 'prestations')]
    public function index(
        ServiceRepository $serviceRepository
    ): Response {
        return $this->render('service/index.html.twig', [
            'page_title' => 'Prestations',
            'services' => $serviceRepository->findActive(),
        ]);
    }

    #[Route('/prestations/{id}', name: 'prestation_show')]
    public function show(
        Service $service,
        ServiceRepository $serviceRepository
    ): Response {
        // Vérifier que le service est actif
        if (!$service->isActive()) {
            throw $this->createNotFoundException('Cette prestation n\'est pas disponible.');
        }

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'page_title' => $service->getTitle(),
            'related_services' => $serviceRepository->findActive(),
        ]);
    }
}