<?php

namespace App\Controller;

use App\Repository\RealisationRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        RealisationRepository $realisationRepository,
        ServiceRepository $serviceRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'page_title' => 'regards singuliers - Architecte d\'intérieur',
            'latest_realisations' => $realisationRepository->findLatest(3),
            'services' => $serviceRepository->findAll(),
        ]);
    }
}