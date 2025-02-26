<?php

namespace App\Controller;

use App\Repository\DiscussionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/discussions')]
class DiscussionsController extends AbstractController
{
    #[Route('/', name: 'discussions', methods: ['GET'])]
    public function index(DiscussionRepository $discussionRepository): Response
    {
        $discussions = $discussionRepository->findAll();

        return $this->render('discussions/index.html.twig', [
            'discussions' => $discussions,
        ]);
    }
}
