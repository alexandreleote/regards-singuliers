<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\BlogPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    protected function getUserCount(UserRepository $userRepository): int
    {
        return $userRepository->count([]);
    }

    protected function getPostCount(BlogPostRepository $blogPostRepository): int
    {
        return $blogPostRepository->count([]);
    }

    protected function renderWithUserCount(
        UserRepository $userRepository, 
        BlogPostRepository $blogPostRepository,
        string $template, 
        array $parameters = []
    ): Response {
        $parameters['users_count'] = $this->getUserCount($userRepository);
        $parameters['posts_count'] = $this->getPostCount($blogPostRepository);
        return $this->render($template, $parameters);
    }
}
