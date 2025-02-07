<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/services/{id}', name: 'service')]
    public function show(Service $service): Response
    {
        return $this->render('service/show.html.twig', [
            'service' => $service,
        ]);
    }
}
