<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Service;
use App\Entity\Realisation;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use App\Repository\RealisationRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Admin\ServiceCrudController;
use App\Controller\Admin\RealisationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private $userRepository;
    private $realisationRepository;
    private $serviceRepository;
    private $adminUrlGenerator;

    public function __construct(
        UserRepository $userRepository,
        RealisationRepository $realisationRepository,
        ServiceRepository $serviceRepository,
        AdminUrlGenerator $adminUrlGenerator
    ) {
        $this->userRepository = $userRepository;
        $this->realisationRepository = $realisationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function index(): Response
    {
        // Statistiques pour le tableau de bord
        $stats = [
            'users' => $this->userRepository->count([]),
            'realisations' => $this->realisationRepository->count([]),
            'services' => count($this->serviceRepository->findActive()),
            'latest_realisations' => $this->realisationRepository->findLatest(5)
        ];

        // Actions rapides
        $actions = [
            [
                'title' => 'Nouvelle réalisation',
                'url' => $this->adminUrlGenerator
                    ->setController(RealisationCrudController::class)
                    ->setAction('new')
                    ->generateUrl(),
                'icon' => 'fa fa-plus'
            ],
            [
                'title' => 'Nouvelle prestation',
                'url' => $this->adminUrlGenerator
                    ->setController(ServiceCrudController::class)
                    ->setAction('new')
                    ->generateUrl(),
                'icon' => 'fa fa-briefcase'
            ]
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'actions' => $actions
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Regards Singuliers - Administration')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        yield MenuItem::section('Gestion des utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        
        yield MenuItem::section('Contenu');
        yield MenuItem::linkToCrud('Prestations', 'fa fa-briefcase', Service::class);
        yield MenuItem::linkToCrud('Réalisations', 'fa fa-image', Realisation::class);
        
        yield MenuItem::section('Liens rapides');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'home');
    }
}