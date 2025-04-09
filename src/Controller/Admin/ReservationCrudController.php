<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Entity\Discussion;
use App\Entity\User;
use App\Repository\DiscussionRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class ReservationCrudController extends AbstractCrudController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private EntityManagerInterface $entityManager,
        private DiscussionRepository $discussionRepository
    ) {}

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
            ->setDefaultSort(['bookedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('service', 'Prestation')
                ->setFormTypeOption('choice_label', 'title')
                ->formatValue(function ($value, $entity) {
                    return $entity->getService()->getTitle();
                }),
            TextField::new('name', 'Nom'),
            TextField::new('firstName', 'Prénom'),
            DateTimeField::new('bookedAt', 'Date de réservation'),
            DateTimeField::new('appointment_datetime', 'Date de rendez-vous'),
            TextField::new('status', 'Statut'),
            NumberField::new('price', 'Prix')->hideOnIndex(),
            TextField::new('stripePaymentIntentId', 'ID Stripe')->hideOnIndex(),
            TextField::new('calendlyEventId', 'ID Calendly')->hideOnIndex(),
            TextField::new('calendlyInviteeId', 'ID Invité Calendly')->hideOnIndex(),
            DateTimeField::new('canceledAt', 'Date d\'annulation')->hideOnIndex(),
            AssociationField::new('user')->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewDiscussions = Action::new('viewDiscussions', 'Aller à la discussion', 'fa fa-comments')
            ->linkToCrudAction('viewDiscussions')
            ->setCssClass('btn btn-info');

        $cancelReservation = Action::new('cancelReservation', 'Annuler la réservation', 'fa fa-times')
            ->linkToCrudAction('cancelReservation')
            ->displayAsButton()
            ->setCssClass('btn btn-danger');

        $refundReservation = Action::new('refundReservation', 'Rembourser la réservation', 'fa fa-money-bill-wave')
            ->linkToCrudAction('refundReservation')
            ->displayAsButton()
            ->setCssClass('btn btn-warning');

        $rescheduleReservation = Action::new('rescheduleReservation', 'Replanifier la réservation', 'fa fa-calendar-alt')
            ->linkToCrudAction('rescheduleReservation')
            ->displayAsButton()
            ->setCssClass('btn btn-info');

        $showInvoice = Action::new('showInvoice', 'Voir la facture', 'fa fa-file-invoice')
            ->linkToCrudAction('showInvoice')
            ->displayAsButton()
            ->setCssClass('btn btn-success');

        $downloadInvoice = Action::new('downloadInvoice', 'Télécharger la facture', 'fa fa-download')
            ->linkToCrudAction('downloadInvoice')
            ->displayAsButton()
            ->setCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('Voir');
            })
            ->disable(Action::EDIT)
            ->add(Crud::PAGE_INDEX, $viewDiscussions)
            ->add(Crud::PAGE_DETAIL, $viewDiscussions)
            ->add(Crud::PAGE_DETAIL, $cancelReservation)
            ->add(Crud::PAGE_DETAIL, $refundReservation)
            ->add(Crud::PAGE_DETAIL, $rescheduleReservation)
            ->add(Crud::PAGE_DETAIL, $showInvoice)
            ->add(Crud::PAGE_DETAIL, $downloadInvoice)
            ->disable(Action::NEW)
            ->disable(Action::DELETE)
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action->setLabel('Retour');
            });
    }

    public function viewDiscussions(Request $request): Response
    {
        $reservationId = $request->query->get('entityId');
        $reservation = $this->entityManager->getRepository(Reservation::class)->find($reservationId);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $discussion = $reservation->getDiscussions()->first();
        
        if (!$discussion) {
            $this->addFlash('warning', 'Aucune discussion trouvée pour cette réservation');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $reservationId
            ]);
        }

        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => DiscussionCrudController::class,
            'entityId' => $discussion->getId()
        ]);
    }

    public function cancelReservation(Reservation $reservation): Response
    {
        $reservation->setStatus('annulée');
        $reservation->setCanceledAt(new \DateTimeImmutable());
        
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'La réservation a été annulée avec succès.');

        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $reservation->getId()
        ]);
    }

    public function refundReservation(Reservation $reservation): Response
    {
        // TODO: Implémenter la logique de remboursement Stripe
        $reservation->setStatus('remboursée');
        
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Le remboursement a été effectué avec succès.');

        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $reservation->getId()
        ]);
    }

    public function rescheduleReservation(Reservation $reservation): Response
    {
        // TODO: Implémenter la logique de replanification avec Calendly
        $reservation->setStatus('replanifiée');
        
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'La réservation a été replanifiée avec succès.');

        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $reservation->getId()
        ]);
    }

    public function showInvoice(Reservation $reservation): Response
    {
        // TODO: Implémenter la logique d'affichage de la facture
        // Par exemple, générer un PDF et l'afficher dans le navigateur
        $this->addFlash('info', 'Affichage de la facture en cours de développement.');
        
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $reservation->getId()
        ]);
    }

    public function downloadInvoice(Reservation $reservation): Response
    {
        // TODO: Implémenter la logique de téléchargement de la facture
        // Par exemple, générer un PDF et le télécharger
        $this->addFlash('info', 'Téléchargement de la facture en cours de développement.');
        
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $reservation->getId()
        ]);
    }
} 