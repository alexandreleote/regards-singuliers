<?php

namespace App\Controller;

use App\Entity\Realisation;
use App\Form\RealisationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/realisations')]
class RealisationsController extends AbstractController
{
    #[Route('/new', name: 'realisation_new', methods: ['GET', 'POST'])]
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
