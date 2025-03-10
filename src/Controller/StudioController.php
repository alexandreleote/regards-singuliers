<?php

namespace App\Controller;

use App\Entity\Studio;
use App\Repository\StudioRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StudioController extends AbstractController
{
    #[Route('/studio', name: 'studio')]
    public function index(StudioRepository $studioRepository)
    {
        $studios = $studioRepository->findAll();
    
        return $this->render('studio/index.html.twig', [
            'studios' => $studios,
            'page_title' => 'regards singuliers - Le Studio'
        ]);
    }
}
