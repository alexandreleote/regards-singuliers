<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ServiceController extends AbstractController
{
    #[Route('/prestations', name: 'prestations')]
    public function index(
        ServiceRepository $serviceRepository
    ): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('service/index.html.twig', [
            'page_title' => 'Prestations',
            'services' => $services,
        ]);
    }
}
