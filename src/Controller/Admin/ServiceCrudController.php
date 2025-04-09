<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
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
            TextField::new('reference', 'Référence de la prestation')
                ->setRequired(true),
            TextField::new('title', 'Intitulé de la prestation')
                ->setRequired(true),
            TextEditorField::new('smallDescription', 'Description courte')
                ->setRequired(true)
                ->setHelp('Une description courte qui apparaîtra dans la liste des prestations')
                ->setFormTypeOption('attr', [
                    'class' => 'text-editor',
                ]),
            TextEditorField::new('description', 'Description de la prestation')
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
            ->setDefaultSort(['reference' => 'ASC']);
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
            ->disable(Action::DETAIL)

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
            $smallDescription = $entityInstance->getSmallDescription();
            $slug = strtolower($this->slugger->slug($entityInstance->getTitle()));
            $entityInstance->setSlug($slug);
            
            if ($description !== null) {
                // Convertir uniquement les entités HTML
                $cleanDescription = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $entityInstance->setDescription($cleanDescription);
            }
            
            if ($smallDescription !== null) {
                // Convertir uniquement les entités HTML
                $cleanSmallDescription = html_entity_decode($smallDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $entityInstance->setSmallDescription($cleanSmallDescription);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Service) {
            $description = $entityInstance->getDescription();
            $smallDescription = $entityInstance->getSmallDescription();
            
            if ($description !== null) {
                // Convertir uniquement les entités HTML
                $cleanDescription = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $entityInstance->setDescription($cleanDescription);
            }
            
            if ($smallDescription !== null) {
                // Convertir uniquement les entités HTML
                $cleanSmallDescription = html_entity_decode($smallDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $entityInstance->setSmallDescription($cleanSmallDescription);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}