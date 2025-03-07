<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/cgu', name: 'cgu')]
    public function cgu(): Response
    {
        return $this->render('legal/cgu.html.twig', [
            'page_title' => 'Conditions Générales d\'Utilisation - regards singuliers',
        ]);
    }

    #[Route('/confidentialite', name: 'confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('legal/confidentialite.html.twig', [
            'page_title' => 'Politique de Confidentialité - regards singuliers',
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig', [
            'page_title' => 'Mentions Légales - regards singuliers',
        ]);
    }
}
