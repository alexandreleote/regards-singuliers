<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setPageTitle('index', 'Liste des réservations')
            ->setPageTitle('detail', 'Détails de la réservation')
            ->setPageTitle('edit', 'Modifier la réservation')
            ->setPageTitle('new', 'Nouvelle réservation')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user', 'Utilisateur'),
            AssociationField::new('service', 'Prestation'),
            DateTimeField::new('date', 'Date de réservation'),
            TextField::new('status', 'Statut'),
            TextareaField::new('message', 'Message initial'),
            DateTimeField::new('createdAt', 'Créée le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifiée le')->hideOnForm(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewDiscussions = Action::new('viewDiscussions', 'Voir les discussions', 'fa fa-comments')
            ->linkToCrudAction('viewDiscussions');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewDiscussions)
            ->add(Crud::PAGE_DETAIL, $viewDiscussions);
    }

    public function viewDiscussions(AdminContext $context)
    {
        $reservation = $context->getEntity()->getInstance();
        
        return $this->render('admin/reservation/discussions.html.twig', [
            'reservation' => $reservation,
            'discussions' => $reservation->getDiscussions(),
        ]);
    }
} 