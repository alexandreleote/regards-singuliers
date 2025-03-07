<?php

namespace App\Controller\Admin;

use App\Entity\Realisation;
use App\Form\RealisationImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RealisationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Realisation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title', 'Titre')
                ->setRequired(true),
            ImageField::new('mainImage', 'Image principale')
                ->setBasePath('uploads/realisations')
                ->setUploadDir('public/uploads/realisations')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(true),
            TextEditorField::new('content', 'Contenu')
                ->setRequired(true),
            CollectionField::new('images', 'Images')
                ->setEntryType(RealisationImageType::class)
                ->setFormTypeOption('by_reference', false)
                ->onlyOnForms(),
            DateTimeField::new('createdAt', 'Date de création')
                ->hideOnForm(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Réalisations')
            ->setPageTitle('new', 'Ajouter une réalisation')
            ->setPageTitle('edit', 'Modifier la réalisation')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Ajouter une réalisation');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Modifier');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            });
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Realisation $entityInstance */
        foreach ($entityInstance->getImages() as $image) {
            if ($image->getFile() instanceof UploadedFile) {
                $newFilename = uniqid().'.'.$image->getFile()->guessExtension();
                $image->getFile()->move(
                    'public/uploads/realisations',
                    $newFilename
                );
                $image->setPath($newFilename);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Realisation $entityInstance */
        foreach ($entityInstance->getImages() as $image) {
            if ($image->getFile() instanceof UploadedFile) {
                $newFilename = uniqid().'.'.$image->getFile()->guessExtension();
                $image->getFile()->move(
                    'public/uploads/realisations',
                    $newFilename
                );
                $image->setPath($newFilename);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}