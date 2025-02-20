<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Repository\BlogPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/blog', name: 'app_blog')]
    public function index(BlogPostRepository $blogPostRepository): Response
    {
        return $this->render('blog/index.html.twig', [
            'posts' => $blogPostRepository->findPublished(),
        ]);
    }

    #[Route('/blog/{slug}', name: 'app_blog_show')]
    public function show(BlogPost $post): Response
    {
        if (!$post->isPublished()) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        return $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);
    }
}
