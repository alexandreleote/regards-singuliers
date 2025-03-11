<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class ContactCrudController extends AbstractCrudController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('type', 'Type de contact')
                ->setChoices([
                    'Particulier' => Contact::TYPE_PARTICULIER,
                    'Professionnel' => Contact::TYPE_PROFESSIONNEL,
                ])
                ->hideOnForm(),
            ChoiceField::new('civilite', 'Civilité')
                ->setChoices([
                    'Monsieur' => Contact::CIVILITE_MONSIEUR,
                    'Madame' => Contact::CIVILITE_MADAME,
                ])
                ->hideOnForm(),
            TextField::new('nom', 'Nom')->hideOnForm(),
            TextField::new('prenom', 'Prénom')->hideOnForm(),
            EmailField::new('email', 'Email')->hideOnForm(),
            TelephoneField::new('telephone', 'Téléphone')->hideOnForm(),
            TextField::new('entreprise', 'Entreprise')
                ->hideOnForm()
                ->hideOnIndex()
                ->setFormTypeOption('row_attr', [
                    'data-field' => 'entreprise',
                    'class' => 'js-professional-field',
                ]),
            TextField::new('localisation', 'Localisation')
                ->hideOnForm()
                ->hideOnIndex()
                ->setFormTypeOption('row_attr', [
                    'data-field' => 'localisation',
                    'class' => 'js-professional-field',
                ]),
            TextareaField::new('description', 'Message')
                ->hideOnIndex()
                ->hideOnForm(),
            DateTimeField::new('createdAt', 'Date d\'envoi')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnForm(),
            BooleanField::new('isRead', 'Lu')
                ->renderAsSwitch(false)
                ->hideOnForm(),
            BooleanField::new('isResponded', 'Répondu')
                ->renderAsSwitch(false)
                ->hideOnForm(),
        ];

        if ($pageName === Crud::PAGE_INDEX) {
            return array_filter($fields, function($field) {
                $propertyName = $field->getAsDto()->getProperty();
                return !in_array($propertyName, [
                    'description', 'entreprise', 'localisation'
                ]);
            });
        }

        return $fields;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Demandes de contact')
            ->setPageTitle('detail', 'Détails de la demande')
            ->setEntityLabelInPlural('Demandes de contact')
            ->setEntityLabelInSingular('Demande de contact')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type', 'Type de contact')
                ->setChoices([
                    'Particulier' => Contact::TYPE_PARTICULIER,
                    'Professionnel' => Contact::TYPE_PROFESSIONNEL,
                ]))
            ->add(TextFilter::new('nom', 'Nom'))
            ->add(TextFilter::new('prenom', 'Prénom'))
            ->add(TextFilter::new('email', 'Email'))
            ->add(TextFilter::new('entreprise', 'Entreprise'))
            ->add(DateTimeFilter::new('createdAt', 'Date d\'envoi'))
            ->add(DateTimeFilter::new('readAt', 'Lu le'))
            ->add(DateTimeFilter::new('respondedAt', 'Répondu le'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $markAsRead = Action::new('markAsRead', 'Marquer comme lu', 'fa fa-eye')
            ->displayIf(static function ($entity) {
                return !$entity->isRead();
            })
            ->linkToCrudAction('markAsRead');

        $markAsResponded = Action::new('markAsResponded', 'Marquer comme répondu', 'fa fa-reply')
            ->displayIf(static function ($entity) {
                return !$entity->isResponded();
            })
            ->linkToCrudAction('markAsResponded');

        return $actions
            ->add(Crud::PAGE_INDEX, $markAsRead)
            ->add(Crud::PAGE_INDEX, $markAsResponded)
            ->add(Crud::PAGE_DETAIL, $markAsRead)
            ->add(Crud::PAGE_DETAIL, $markAsResponded)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            })
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('Détails');
            });
    }

    public function markAsRead(AdminContext $adminContext): Response
    {
        $contact = $adminContext->getEntity()->getInstance();
        
        if (!$contact instanceof Contact) {
            throw new \LogicException('Entity is missing or not a Contact');
        }

        $contact->markAsRead();
        $this->entityManager->flush();

        $this->addFlash('success', 'La demande a été marquée comme lue.');

        return $this->redirect($adminContext->getReferrer());
    }

    public function markAsResponded(AdminContext $adminContext): Response
    {
        $contact = $adminContext->getEntity()->getInstance();
        
        if (!$contact instanceof Contact) {
            throw new \LogicException('Entity is missing or not a Contact');
        }

        $contact->markAsResponded();
        $this->entityManager->flush();

        $this->addFlash('success', 'La demande a été marquée comme répondue.');

        return $this->redirect($adminContext->getReferrer());
    }
}