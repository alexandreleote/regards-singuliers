<?php

namespace App\Controller\Admin;

use App\Entity\Studio;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Doctrine\ORM\EntityManagerInterface;

class StudioCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Studio::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextEditorField::new('content', 'Contenu de la page')
                ->setRequired(true)
                ->setFormTypeOption('attr', [
                    'class' => 'text-editor',
                ]),
        ];
    }

    public function configureCrud(Crud $crud): Crud 
    {
        return $crud
            ->setPageTitle('edit', 'Modifier la page')
            ->setPageTitle('new', 'Ajouter une page')
            ->setEntityLabelInPlural('Pages')
            ->setEntityLabelInSingular('Page');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            /* INDEX */
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Ajouter une page');
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
                return $action->setLabel('Sauvegarder et ajouter une autre page');
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
        if ($entityInstance instanceof Studio) {
            $content = $entityInstance->getContent();
            if ($content !== null) {
                // Autoriser les balises HTML pour le formatage du texte
                $cleanContent = strip_tags($content, '<strong><i><em><br><p>');
                // Supprimer les balises vides
                $cleanContent = preg_replace('/<(strong|i|em|p)>\s*<\/\1>/', '', $cleanContent);
                $entityInstance->setContent($cleanContent);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Studio) {
            $content = $entityInstance->getContent();
            if ($content !== null) {
                // Autoriser les balises HTML pour le formatage du texte
                $cleanContent = strip_tags($content, '<strong><i><em><br><p>');
                // Supprimer les balises vides
                $cleanContent = preg_replace('/<(strong|i|em|p)>\s*<\/\1>/', '', $cleanContent);
                $entityInstance->setContent($cleanContent);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}