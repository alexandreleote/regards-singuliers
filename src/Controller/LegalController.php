<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales/conditions-generales-utilisation', name: 'cgu')]
    public function cgu(): Response
    {
        return $this->render('legal/cgu.html.twig', [
            'page_title' => 'Conditions générales d\'utilisation - regards singuliers',
            'meta_description' => 'Conditions générales d\'utilisation - regards singuliers',
        ]);
    }
    
    #[Route('/mentions-legales/politique-confidentialite', name: 'confidentialite')]
    public function privacy(): Response
    {
        return $this->render('legal/confidentialite.html.twig', [
            'page_title' => 'Politique de confidentialité - regards singuliers',
            'meta_description' => 'Politique de confidentialité - regards singuliers',
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig', [
            'page_title' => 'Mentions Légales - regards singuliers',
            'meta_description' => 'Mentions Légales - regards singuliers',
        ]);
    }

    #[Route('/faq', name: 'faq')]
    public function faq(): Response
    {
        return $this->render('legal/faq.html.twig', [
            'page_title' => 'FAQ - regards singuliers',
            'meta_description' => 'FAQ - regards singuliers',
        ]);
    }
}
