<?php

namespace App\Controller;

use App\Entity\LegalPage;
use App\Repository\LegalPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegalController extends AbstractController
{
    #[Route('/legal/{slug}', name: 'app_legal_page')]
    public function show(LegalPage $legalPage): Response
    {
        if (!$legalPage->isIsPublished()) {
            throw $this->createNotFoundException('Cette page n\'existe pas.');
        }

        return $this->render('legal/show.html.twig', [
            'page' => $legalPage,
        ]);
    }
}
