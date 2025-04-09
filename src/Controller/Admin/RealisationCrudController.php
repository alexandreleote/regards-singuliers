<?php

namespace App\Controller\Admin;

use App\Entity\Realisation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RealisationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Realisation::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Modifier');
            })
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('Voir');
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réalisation')
            ->setEntityLabelInPlural('Réalisations')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $uploadDir = 'uploads/realisations';
        $uploadPath = $this->getParameter('kernel.project_dir').'/public/'.$uploadDir;

        yield TextField::new('title', 'Titre');
        yield TextEditorField::new('content', 'Contenu');
        yield DateTimeField::new('createdAt', 'Date de création')
            ->hideOnForm();
        
        yield ImageField::new('mainImage', 'Image principale')
            ->setBasePath($uploadDir)
            ->setUploadDir('public/'.$uploadDir)
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired($pageName === Crud::PAGE_NEW);

        yield ImageField::new('additionalImages', 'Images additionnelles')
            ->setBasePath($uploadDir)
            ->setUploadDir('public/'.$uploadDir)
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setFormTypeOption('multiple', true)
            ->setRequired(false)
            ->onlyOnForms();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Realisation $entityInstance */
        
        // Gestion de l'image principale
        $mainImageFile = $this->getContext()->getRequest()->files->get('Realisation')['mainImage'] ?? null;
        if ($mainImageFile instanceof UploadedFile) {
            $newFilename = uniqid().'.'.$mainImageFile->guessExtension();
            $mainImageFile->move(
                $this->getParameter('kernel.project_dir').'/public/uploads/realisations',
                $newFilename
            );
            $entityInstance->setMainImage($newFilename);
        }

        // Gestion des images additionnelles
        $additionalFiles = $this->getContext()->getRequest()->files->get('Realisation')['additionalImages'] ?? [];
        if (is_array($additionalFiles)) {
            foreach ($additionalFiles as $file) {
                if ($file instanceof UploadedFile) {
                    $newFilename = uniqid().'.'.$file->guessExtension();
                    $file->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/realisations',
                        $newFilename
                    );
                    $entityInstance->addAdditionalImage($newFilename);
                }
            }
        }

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Realisation $entityInstance */
        
        // Gestion de l'image principale
        $mainImageFile = $this->getContext()->getRequest()->files->get('Realisation')['mainImage'] ?? null;
        if ($mainImageFile instanceof UploadedFile) {
            $newFilename = uniqid().'.'.$mainImageFile->guessExtension();
            $mainImageFile->move(
                $this->getParameter('kernel.project_dir').'/public/uploads/realisations',
                $newFilename
            );
            $entityInstance->setMainImage($newFilename);
        }

        // Gestion des images additionnelles
        $additionalFiles = $this->getContext()->getRequest()->files->get('Realisation')['additionalImages'] ?? [];
        if (is_array($additionalFiles)) {
            foreach ($additionalFiles as $file) {
                if ($file instanceof UploadedFile) {
                    $newFilename = uniqid().'.'.$file->guessExtension();
                    $file->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/realisations',
                        $newFilename
                    );
                    $entityInstance->addAdditionalImage($newFilename);
                }
            }
        }

        $entityManager->flush();
    }
}