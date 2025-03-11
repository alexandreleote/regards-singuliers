<?php

namespace App\Controller\Admin;

use App\Entity\Studio;
use App\Entity\Service;
use App\Entity\Realisation;
use App\Entity\User;
use App\Entity\Contact;
use App\Repository\UserRepository;
use App\Repository\RealisationRepository;
use App\Repository\ServiceRepository;
use App\Repository\ContactRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private $userRepository;
    private $realisationRepository;
    private $serviceRepository;
    private $contactRepository;

    public function __construct(
        UserRepository $userRepository,
        RealisationRepository $realisationRepository,
        ServiceRepository $serviceRepository,
        ContactRepository $contactRepository
    ) {
        $this->userRepository = $userRepository;
        $this->realisationRepository = $realisationRepository;
        $this->serviceRepository = $serviceRepository;
        $this->contactRepository = $contactRepository;
    }

    public function index(): Response
    {
        // Vérifier si nous sommes dans un contexte CRUD
        $request = $this->container->get('request_stack')->getCurrentRequest();
        
        // Si nous avons des paramètres CRUD, utiliser le comportement par défaut d'EasyAdmin
        if ($request->query->has('crudController')) {
            return parent::index();
        }
        
        // Sinon, afficher notre tableau de bord personnalisé
        return $this->dashboard();
    }
    
    private function dashboard(): Response
    {
        // Récupérer les statistiques pour le tableau de bord
        $totalUsers = $this->userRepository->count([]);
        $totalRealisations = $this->realisationRepository->count([]);
        $totalServices = $this->serviceRepository->count([]);
        $totalContacts = $this->contactRepository->count([]);
        $unreadContacts = $this->contactRepository->count(['readAt' => null]);
        
        // Récupérer les utilisateurs récemment inscrits
        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Récupérer les dernières réalisations ajoutées
        $recentRealisations = $this->realisationRepository->findBy([], ['createdAt' => 'DESC'], 3);
        
        // Récupérer les derniers contacts reçus
        $recentContacts = $this->contactRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // Ajouter des données personnalisées
        $customData = [
            'totalUsers' => $totalUsers,
            'totalRealisations' => $totalRealisations,
            'totalServices' => $totalServices,
            'totalContacts' => $totalContacts,
            'unreadContacts' => $unreadContacts,
            'recentUsers' => $recentUsers,
            'recentRealisations' => $recentRealisations,
            'recentContacts' => $recentContacts,
        ];

        return $this->render('admin/dashboard/index.html.twig', [
            'customData' => $customData,
            'page_title' => 'Tableau de bord',
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Tableau de bord')
            ->setFaviconPath('favicon-admin.png')
            ->setTranslationDomain('admin')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Retourner sur le site', 'fa fa-chevron-left', 'home');
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-table-columns');
        yield MenuItem::linkToCrud('Le Studio', 'fa fa-file-pen', Studio::class);
        yield MenuItem::linkToCrud('Projets', 'fa fa-file-pen', Realisation::class);
        yield MenuItem::linkToCrud('Prestations', 'fa fa-gear', Service::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Demandes de contact', 'fa fa-envelope', Contact::class);
    }
}