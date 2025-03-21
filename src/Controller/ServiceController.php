<?php

namespace App\Controller;

use App\Entity\Service;
use Doctrine\ORM\Mapping\Entity;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

final class ServiceController extends AbstractController
{
    #[Route('/prestations', name: 'prestations')]
    public function index(
        ServiceRepository $serviceRepository
    ): Response {
        return $this->render('service/index.html.twig', [
            'page_title' => 'Prestations - regards singuliers',
            'meta_description' => 'Prestations - regards singuliers',
            'services' => $serviceRepository->findActive(),
        ]);
    }

    #[Route('/prestations/{slug:service}', name: 'prestation_show')]
    public function show(
        Service $service,
        ServiceRepository $serviceRepository
    ): Response {
        // Vérifier que le service est actif
        if (!$service) {
            throw $this->createNotFoundException('Cette prestation n\'existe pas.');
        }

        if (!$service->isActive()) {
            throw $this->createNotFoundException('Cette prestation n\'est pas disponible.');
        }

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'page_title' => $service->getTitle() . ' - regards singuliers',
            'meta_description' => $service->getTitle() . ' - regards singuliers',
            'related_services' => $serviceRepository->findActive(),
        ]);
    }

}