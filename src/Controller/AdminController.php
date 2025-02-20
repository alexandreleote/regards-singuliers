<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\BlogPost;
use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        // Récupérer le nombre d'utilisateurs
        $usersCount = $this->entityManager->getRepository(User::class)->count([]);

        // Récupérer le nombre d'articles de blog
        $postsCount = $this->entityManager->getRepository(BlogPost::class)->count([]);

        // Récupérer les rendez-vous du jour
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        $appointments = $this->entityManager->getRepository(Appointment::class)
            ->createQueryBuilder('a')
            ->where('a.startTime >= :today')
            ->andWhere('a.startTime < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'users_count' => $usersCount,
            'posts_count' => $postsCount,
            'appointments' => $appointments,
        ]);
    }

    #[Route('/pages', name: 'app_admin_pages')]
    public function pages(): Response
    {
        return $this->render('admin/pages.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/blog', name: 'app_admin_blog')]
    public function blog(): Response
    {
        return $this->render('admin/blog.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/realisations', name: 'app_admin_realisations')]
    public function realisations(): Response
    {
        return $this->render('admin/realisations.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/prestations', name: 'app_admin_prestations')]
    public function prestations(): Response
    {
        return $this->render('admin/prestations.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/entreprise', name: 'app_admin_entreprise')]
    public function entreprise(): Response
    {
        return $this->render('admin/entreprise.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
