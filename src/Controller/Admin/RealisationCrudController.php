<?php

namespace App\Controller\Admin;

use App\Entity\Realisation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RealisationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Realisation::class;
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

        yield ImageField::new('imageFiles', 'Images additionnelles')
            ->setBasePath($uploadDir)
            ->setUploadDir('public/'.$uploadDir)
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setFormTypeOption('multiple', true)
            ->onlyOnForms();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Realisation $entityInstance */
        
        // Gérer les images additionnelles
        if ($imageFiles = $entityInstance->getImageFiles()) {
            foreach ($imageFiles as $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move(
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
        
        // Gérer les images additionnelles
        if ($imageFiles = $entityInstance->getImageFiles()) {
            foreach ($imageFiles as $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move(
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