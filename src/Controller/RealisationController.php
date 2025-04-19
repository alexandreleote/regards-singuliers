<?php

namespace App\Controller;

use App\Entity\Realisation;
use App\Form\RealisationType;
use App\Repository\RealisationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

final class RealisationController extends AbstractController
{
    #[Route('/realisations', name: 'realisations')]
    public function index(RealisationRepository $realisationRepository): Response
    {
        return $this->render('realisation/index.html.twig', [
            'page_title' => 'Nos réalisations - regards singuliers',
            'meta_description' => 'Découvrez l\'univers créatif de notrer studio d\'architecture d\'intérieur. Un porftolio exclusif de projets sur-mesure, alliant élégance, fonctionnalité et innovation pour transformer vos espaces de vie.',
            'realisations' => $realisationRepository->findWithImages(),
            'active_page' => 'realisations',
        ]);
    }

    #[Route('/realisations/{id}', name: 'realisation_show')]
    public function show(
        Realisation $realisation,
        RealisationRepository $realisationRepository
    ): Response {
        return $this->render('realisation/show.html.twig', [
            'page_title' => $realisation->getTitle() . ' - regards singuliers',
            'meta_description' => $realisation->getTitle() . ' - regards singuliers',
            'realisation' => $realisation,
            'other_realisations' => $realisationRepository->findLatest(3),
            'active_page' => 'realisations',
        ]);
    }

    #[Route('/realisations/create', name: 'realisation_create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $realisation = new Realisation();
        $form = $this->createForm(RealisationType::class, $realisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image principale
            if ($mainImageFile = $form->get('mainImage')->getData()) {
                $newFilename = uniqid().'.'.$mainImageFile->guessExtension();
                $mainImageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/realisations',
                    $newFilename
                );
                $realisation->setMainImage($newFilename);
            }

            // Gestion des images additionnelles
            if ($additionalFiles = $form->get('imageFiles')->getData()) {
                foreach ($additionalFiles as $file) {
                    $newFilename = uniqid().'.'.$file->guessExtension();
                    $file->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/realisations',
                        $newFilename
                    );
                    $realisation->addAdditionalImage($newFilename);
                }
            }

            $entityManager->persist($realisation);
            $entityManager->flush();

            return $this->redirectToRoute('realisations');
        }

        return $this->render('realisation/create.html.twig', [
            'page_title' => 'Créer une réalisation - regards singuliers',
            'meta_description' => 'Créer une réalisation - regards singuliers',
            'form' => $form->createView(),
        ]);
    }
}