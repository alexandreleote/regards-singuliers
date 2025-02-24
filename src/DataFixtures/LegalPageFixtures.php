<?php

namespace App\DataFixtures;

use App\Entity\LegalPage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class LegalPageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();
        $now = new \DateTime();
        
        $pages = [
            [
                'title' => 'CGU / CGV',
                'content' => 'Conditions Générales d\'Utilisation et de Vente...',
                'isPublished' => true
            ],
            [
                'title' => 'Politique de confidentialité',
                'content' => 'Politique de confidentialité et protection des données...',
                'isPublished' => true
            ],
            [
                'title' => 'Mentions légales',
                'content' => 'Mentions légales du site...',
                'isPublished' => true
            ],
        ];

        foreach ($pages as $page) {
            $legalPage = new LegalPage();
            $legalPage->setTitle($page['title']);
            $legalPage->setContent($page['content']);
            $legalPage->setIsPublished($page['isPublished']);
            
            // Générer le slug manuellement
            $slug = $slugger->slug(strtolower($page['title']));
            $legalPage->setSlug($slug);
            
            // Définir les timestamps
            $legalPage->setCreatedAt($now);
            $legalPage->setUpdatedAt($now);
            
            $manager->persist($legalPage);
        }

        $manager->flush();
    }
}
