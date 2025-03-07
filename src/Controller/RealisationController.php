<?php

namespace App\Controller;

use App\Entity\Realisation;
use App\Repository\RealisationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RealisationController extends AbstractController
{
    #[Route('/realisations', name: 'realisations')]
    public function index(RealisationRepository $realisationRepository): Response
    {
        return $this->render('realisation/index.html.twig', [
            'page_title' => 'Nos réalisations',
            'realisations' => $realisationRepository->findAll(),
        ]);
    }

    #[Route('/realisations/{id}', name: 'realisation_show')]
    public function show(Realisation $realisation): Response
    {
        return $this->render('realisation/show.html.twig', [
            'page_title' => $realisation->getTitle(),
            'realisation' => $realisation,
        ]);
    }
}