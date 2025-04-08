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
            'page_title' => 'regards singuliers - Architecture d\'intérieur',
            'meta_description' => 'regards singuliers accompagne la rénovation de vos espaces en respectant le bâti existant, et en créant des environnements vivants, fonctionnels et adaptés à vos besoins.',
            'latest_realisations' => $realisationRepository->findLatest(3),
            'services' => $serviceRepository->findMostBooked(3),
        ]);
    }

    #[Route('/studio', name: 'studio')]
    public function studio(): Response
    {
        return $this->render('studio/index.html.twig', [
            'page_title' => 'Le Studio - regards singuliers',
            'meta_description' => 'Le studio regards singuliers se distingue par son approche unique de l’architecture d’intérieur, mettant l’accent sur des rénovations créatives et personnalisées qui insufflent une nouvelle vie à vos espaces.',
        ]);
    }
}