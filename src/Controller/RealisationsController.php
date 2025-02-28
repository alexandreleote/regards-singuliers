<?php

namespace App\Controller;

use App\Entity\Realisation;
use App\Form\RealisationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RealisationsController extends AbstractController
{
    #[Route('/realisations', name: 'realisations')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $realisations = $entityManager->getRepository(Realisation::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('realisations/index.html.twig', [
            'realisations' => $realisations,
        ]);
    }

    #[Route('/admin/realisations', name: 'admin_realisations')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(EntityManagerInterface $entityManager): Response
    {
        $realisations = $entityManager->getRepository(Realisation::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('realisations/adminIndex.html.twig', [
            'realisations' => $realisations,
        ]);
    }

    #[Route('/admin/realisations/ajouter', name: 'admin_realisation_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $realisation = new Realisation();
        $form = $this->createForm(RealisationType::class, $realisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($realisation);
            $entityManager->flush();

            $this->addFlash('success', 'Réalisation créée avec succès');
            return $this->redirectToRoute('admin');
        }

        return $this->render('realisations/new.html.twig', [
            'realisation' => $realisation,
            'form' => $form,
        ]);
    }
}
