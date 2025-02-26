<?php

namespace App\Controller;

use App\Repository\DiscussionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(DiscussionRepository $discussionRepository): Response
    {
        // Fetch the three most recent discussions with messages
        $discussions = $discussionRepository->findRecentDiscussionsWithMessages(3);

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'discussions' => $discussions,
        ]);
    }
}
