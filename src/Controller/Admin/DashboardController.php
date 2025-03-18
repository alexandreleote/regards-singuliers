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
        // Statistiques pour le tableau de bord
        $stats = [
            'users' => $this->userRepository->count([]),
            'realisations' => $this->realisationRepository->count([]),
            'services' => $this->serviceRepository->findActive(),
            'contacts' => $this->contactRepository->count([]),
            'unread_contacts' => $this->contactRepository->countUnreadMessages(),
            'professional_contacts' => $this->contactRepository->findByType(Contact::TYPE_PROFESSIONNEL),
            'latest_realisations' => $this->realisationRepository->findLatest(5),
            'pending_contacts' => $this->contactRepository->findUnrespondedProfessional()
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats
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
        yield MenuItem::linkToCrud('Services', 'fa fa-briefcase', Service::class);
        yield MenuItem::linkToCrud('Réalisations', 'fa fa-image', Realisation::class);
        
        yield MenuItem::section('Communication');
        yield MenuItem::linkToCrud('Contacts', 'fa fa-envelope', Contact::class)
            ->setBadge($this->contactRepository->countUnreadMessages(), 'warning');
        
        yield MenuItem::section('Liens rapides');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'home');
    }
}