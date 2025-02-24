<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\BlogPostRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(
        UserRepository $userRepository,
        BlogPostRepository $blogPostRepository,
        ServiceRepository $serviceRepository
    ): Response {
        $users_count = $userRepository->count([]);
        $posts_count = $blogPostRepository->count([]);
        $services_count = $serviceRepository->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'users_count' => $users_count,
            'posts_count' => $posts_count,
            'services_count' => $services_count,
        ]);
    }
}
