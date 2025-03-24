<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ServiceCrudController extends AbstractCrudController
{

    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getEntityFqcn(): string
    {
        return Service::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom du service')
                ->setRequired(true),
            TextField::new('title', 'Titre')
                ->setRequired(true),
            TextEditorField::new('description', 'Description')
                ->setRequired(true)
                ->setFormTypeOption('attr', [
                    'class' => 'text-editor',
                ]),
            MoneyField::new('price', 'Prix')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->setRequired(true)
                ->setNumDecimals(2)
                ->formatValue(function ($value, $entity) {
                    if (null === $value) {
                        return null;
                    }
                    return number_format($value, 2, ',', ' ') . ' €';
                }),
            BooleanField::new('isActive', 'Service actif')
                ->setRequired(true)
                ->renderAsSwitch(true),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('edit', 'Modifier la prestation')
            ->setPageTitle('new', 'Ajouter une prestation')
            ->setPageTitle('index', 'Prestations')
            ->setEntityLabelInPlural('Prestations')
            ->setEntityLabelInSingular('Prestation')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            /* INDEX */
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Ajouter une prestation');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Modifier');
            })

            /* NEW */
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Sauvegarder et terminer');
            })
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, function (Action $action) {
                return $action->setLabel('Sauvegarder et ajouter une autre prestation');
            })
            /* EDIT */
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Sauvegarder et terminer');
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setLabel('Sauvegarder et continuer de modifier');
            });
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Service) {
            $description = $entityInstance->getDescription();
            $slug = strtolower($this->slugger->slug($entityInstance->getTitle()));
            $entityInstance->setSlug($slug);
            if ($description !== null) {
                // Autoriser les balises HTML pour le formatage riche
                $allowedTags = '<strong><em><i><b><ul><ol><li><p><br><span>';
                $cleanDescription = strip_tags($description, $allowedTags);
                $entityInstance->setDescription($cleanDescription);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Service) {
            $description = $entityInstance->getDescription();
            if ($description !== null) {
                // Autoriser les balises HTML pour le formatage riche
                $allowedTags = '<strong><em><i><b><ul><ol><li><p><br><span>';
                $cleanDescription = strip_tags($description, $allowedTags);
                $entityInstance->setDescription($cleanDescription);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}