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
            'meta_description' => 'Consultez les conditions générales d\'utilisation de regards singuliers, régissant l\'accès et l\'utilisation de notre site d\'architecture d\'intérieur.',
            'active_page' => 'cgu'
        ]);
    }
    
    #[Route('/mentions-legales/conditions-generales-vente', name: 'cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig', [
            'page_title' => 'Conditions générales de vente - regards singuliers',
            'meta_description' => 'Consultez les conditions générales de vente de regards singuliers, régissant l\'ensemble de nos services d\'architecture d\'intérieur.',
            'active_page' => 'cgv'
        ]);
    }
    
    #[Route('/mentions-legales/politique-confidentialite', name: 'confidentialite')]
    public function privacy(): Response
    {
        return $this->render('legal/confidentialite.html.twig', [
            'page_title' => 'Politique de confidentialité - regards singuliers',
            'meta_description' => 'Prenez connaissance de la politique de confidentialité de regards singuliers, détaillant la collecte, l\'utilisation et la protection de vos données personnelles sur notre site et nos services.',
            'active_page' => 'confidentialite'
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig', [
            'page_title' => 'Mentions Légales - regards singuliers',
            'meta_description' => 'Accédez aux mentions légales de regards singuliers, fournissant des informations sur l\'éditeur du site, les conditions d\'utilisation et les aspects juridiques liés à nos services.',
            'active_page' => 'mentions_legales'
        ]);
    }

    #[Route('/faq', name: 'faq')]
    public function faq(): Response
    {
        return $this->render('legal/faq.html.twig', [
            'page_title' => 'FAQ - regards singuliers',
            'meta_description' => 'Trouvez les réponses à vos questions fréquentes concernant les services, le processus et les modalités de collaboration avec regards singuliers.',
            'active_page' => 'faq'
        ]);
    }
    
    #[Route('/mentions-legales/gestion-cookies', name: 'cookies')]
    public function cookies(): Response
    {
        return $this->render('legal/cookies.html.twig', [
            'page_title' => 'Gestion des cookies - regards singuliers',
            'meta_description' => 'Gérez vos préférences en matière de cookies sur le site regards singuliers. Apprenez comment nous utilisons les cookies et comment les supprimer.',
            'active_page' => 'cookies'
        ]);
    }
}
