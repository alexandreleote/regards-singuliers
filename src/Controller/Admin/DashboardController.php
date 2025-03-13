<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Service;
use App\Entity\Realisation;
use App\Repository\UserRepository;
use App\Repository\ContactRepository;
use App\Repository\ServiceRepository;
use App\Repository\RealisationRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Admin\ServiceCrudController;
use App\Controller\Admin\RealisationCrudController;
use App\Controller\Admin\ContactCrudController;
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
    private $contactRepository;

    public function __construct(
        UserRepository $userRepository,
        RealisationRepository $realisationRepository,
        ServiceRepository $serviceRepository,
        ContactRepository $contactRepository,
        AdminUrlGenerator $adminUrlGenerator
    ) {
        $this->userRepository = $userRepository;
        $this->realisationRepository = $realisationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->contactRepository = $contactRepository;
    }

    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        // Statistiques pour le tableau de bord
        $stats = [
            'users' => $this->userRepository->count([]),
            'realisations' => $this->realisationRepository->count([]),
            'services' => $this->serviceRepository->count([]),
        ];

        // Actions rapides
        $actions = [
            [
                'title' => 'Nouvelle réalisation',
                'url' => $adminUrlGenerator
                    ->setController(RealisationCrudController::class)
                    ->setAction('new')
                    ->generateUrl(),
                'icon' => 'fa fa-plus'
            ],
            [
                'title' => 'Nouveau service',
                'url' => $adminUrlGenerator
                    ->setController(ServiceCrudController::class)
                    ->setAction('new')
                    ->generateUrl(),
                'icon' => 'fa fa-gear'
            ],
            [
                'title' => 'Lire les demandes de contact',
                'url' => $adminUrlGenerator
                    ->setController(ContactCrudController::class)
                    ->setAction('index')
                    ->generateUrl(),
                'icon' => 'fa fa-envelope'
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
            ->setTitle('regards singuliers')
            ->setFaviconPath('favicon-admin.png')
            ->setTranslationDomain('admin')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Retourner sur le site', 'fa fa-chevron-left', 'home');
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-table-columns');
        yield MenuItem::linkToCrud('Projets', 'fa fa-file-pen', Realisation::class);
        yield MenuItem::linkToCrud('Prestations', 'fa fa-gear', Service::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Contact', 'fa fa-envelope', Contact::class);
    }
}