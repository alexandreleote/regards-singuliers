<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrestationsController extends AbstractController
{
    #[Route('/prestations', name: 'app_prestations')]
    public function index(ServiceRepository $serviceRepository): Response
    {
        return $this->render('prestations/index.html.twig', [
            'services' => $serviceRepository->findAll(),
        ]);
    }
}
