<?php

namespace App\Controller;

use App\Repository\DiscussionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DiscussionsController extends AbstractController
{
    #[Route('/discussions', name: 'discussions')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(DiscussionRepository $discussionRepository): Response
    {
        $discussions = $discussionRepository->findAll();

        return $this->render('discussions/index.html.twig', [
            'discussions' => $discussions,
        ]);
    }

    #[Route('/admin/discussions', name: 'admin_discussions')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(DiscussionRepository $discussionRepository): Response
    {
        $discussions = $discussionRepository->findAll();

        return $this->render('discussions/index.html.twig', [
            'discussions' => $discussions,
        ]);
    }
}
