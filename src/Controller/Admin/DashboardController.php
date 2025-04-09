<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Service;
use App\Entity\Realisation;
use App\Entity\Discussion;
use App\Entity\Reservation;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use App\Repository\RealisationRepository;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Admin\ServiceCrudController;
use App\Controller\Admin\RealisationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private $userRepository;
    private $realisationRepository;
    private $serviceRepository;
    private $discussionRepository;
    private $adminUrlGenerator;
    private $entityManager;

    public function __construct(
        UserRepository $userRepository,
        RealisationRepository $realisationRepository,
        ServiceRepository $serviceRepository,
        DiscussionRepository $discussionRepository,
        AdminUrlGenerator $adminUrlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->realisationRepository = $realisationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->discussionRepository = $discussionRepository;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->entityManager = $entityManager;
    }

    public function index(): Response
    {
        // Statistiques pour le tableau de bord
        // Récupérer les 3 dernières discussions avec des messages
        $qb = $this->entityManager->createQueryBuilder();
        $latestDiscussions = $qb->select('d')
            ->from(Discussion::class, 'd')
            ->leftJoin('d.messages', 'm')
            ->groupBy('d.id')
            ->having('COUNT(m.id) > 0')
            ->orderBy('MAX(m.sentAt)', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        $stats = [
            'users' => $this->userRepository->count([]),
            'realisations' => $this->realisationRepository->count([]),
            'services' => count($this->serviceRepository->findActive()),
            'latest_realisations' => $this->realisationRepository->findLatest(5),
            'latest_discussions' => $latestDiscussions
        ];

        // Actions rapides
        $actions = [
            [
                'title' => 'Nouvelle réalisation',
                'url' => $this->adminUrlGenerator
                    ->setController(RealisationCrudController::class)
                    ->setAction(Action::NEW)
                    ->generateUrl(),
                'icon' => 'fas fa-plus',
                'color' => 'bg-blue-500'
            ],
            [
                'title' => 'Nouvelle prestation',
                'url' => $this->adminUrlGenerator
                    ->setController(ServiceCrudController::class)
                    ->setAction(Action::NEW)
                    ->generateUrl(),
                'icon' => 'fas fa-plus',
                'color' => 'bg-green-500'
            ],
            [
                'title' => 'Gérer Calendly',
                'url' => 'https://calendly.com/event_types/user/me',
                'icon' => 'fas fa-calendar',
                'color' => 'bg-cyan-500',
                'external' => true
            ],
            [
                'title' => 'Gérer Stripe',
                'url' => 'https://dashboard.stripe.com/test/payments',
                'icon' => 'fas fa-credit-card',
                'color' => 'bg-purple-500',
                'external' => true
            ],
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'actions' => $actions
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('regards singuliers - Administration')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Accueil');
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa-solid fa-table-columns');
        
        yield MenuItem::section('Gestion des utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Discussions', 'fa fa-comments', Discussion::class);
        
        yield MenuItem::section('Gestion des prestations');
        yield MenuItem::linkToCrud('Prestations', 'fa fa-briefcase', Service::class);
        yield MenuItem::linkToCrud('Réservations', 'fa fa-calendar', Reservation::class);

        yield MenuItem::section('Gestion des réalisations');
        yield MenuItem::linkToCrud('Réalisations', 'fa fa-image', Realisation::class);
        
        yield MenuItem::section('Liens rapides');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'home');
    }
}