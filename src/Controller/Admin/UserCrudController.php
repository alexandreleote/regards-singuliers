<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserCrudController extends AbstractCrudController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email', 'Email'),
            BooleanField::new('isVerified', 'Vérifié'),
            ChoiceField::new('roles', 'Rôles')
                ->setChoices([
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(),
            FormField::addPanel('Informations personnelles'),
            TextField::new('name', 'Nom'),
            TextField::new('firstName', 'Prénom'),
            TelephoneField::new('phoneNumber', 'Téléphone'),
            FormField::addPanel('Adresse'),
            TextField::new('streetNumber', 'N° de rue')->hideOnIndex(),
            TextField::new('streetName', 'Nom de rue')->hideOnIndex(),
            TextField::new('city', 'Ville'),
            TextField::new('zip', 'Code postal')->hideOnIndex(),
            TextField::new('region', 'Région')->hideOnIndex(),
            FormField::addPanel('Informations système'),
            DateTimeField::new('createdAt', 'Date d\'inscription')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnForm(),
            DateTimeField::new('bannedAt', 'Date de bannissement')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnForm(),
        ];

        if ($pageName === Crud::PAGE_INDEX) {
            return array_filter($fields, function($field) {
                $propertyName = $field->getAsDto()->getProperty();
                return !in_array($propertyName, [
                    'streetNumber', 'streetName', 'zip', 'region'
                ]) && !$field instanceof FormField;
            });
        }

        return $fields;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Utilisateurs')
            ->setPageTitle('edit', 'Modifier l\'utilisateur')
            ->setPageTitle('detail', 'Détails de l\'utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setEntityLabelInSingular('Utilisateur')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isVerified', 'Vérifié'))
            ->add(DateTimeFilter::new('createdAt', 'Date d\'inscription'))
            ->add(DateTimeFilter::new('bannedAt', 'Banni'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewProfile = Action::new('viewProfile', 'Voir le profil', 'fa fa-user')
            ->linkToCrudAction('viewProfile');

        $temporaryBanUser = Action::new('temporaryBanUser', 'Bannir temporairement', 'fa fa-clock-o')
            ->displayIf(static function ($entity) {
                return $entity->getBannedAt() === null;
            })
            ->linkToCrudAction('temporaryBanUser');

        $permanentBanUser = Action::new('permanentBanUser', 'Bannir définitivement', 'fa fa-ban')
            ->displayIf(static function ($entity) {
                return $entity->getBannedAt() === null;
            })
            ->linkToCrudAction('permanentBanUser');

        $unbanUser = Action::new('unbanUser', 'Débannir', 'fa fa-unlock')
            ->displayIf(static function ($entity) {
                return $entity->getBannedAt() !== null;
            })
            ->linkToCrudAction('unbanUser');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewProfile)
            ->add(Crud::PAGE_INDEX, $temporaryBanUser)
            ->add(Crud::PAGE_INDEX, $permanentBanUser)
            ->add(Crud::PAGE_INDEX, $unbanUser)
            ->add(Crud::PAGE_DETAIL, $viewProfile)
            ->add(Crud::PAGE_DETAIL, $temporaryBanUser)
            ->add(Crud::PAGE_DETAIL, $permanentBanUser)
            ->add(Crud::PAGE_DETAIL, $unbanUser)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Modifier');
            })
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('Détails');
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Sauvegarder et terminer');
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setLabel('Sauvegarder et continuer');
            });
    }

    public function viewProfile(AdminContext $adminContext): Response
    {
        $user = $adminContext->getEntity()->getInstance();
        
        if (!$user instanceof User) {
            throw new \LogicException('Entity is missing or not a User');
        }

        // Rediriger vers la page de profil de l'utilisateur
        return $this->redirectToRoute('profile', [
            'id' => $user->getId()
        ]);
    }

    public function temporaryBanUser(AdminContext $adminContext, Request $request): Response
    {
        $user = $adminContext->getEntity()->getInstance();
        
        if (!$user instanceof User) {
            throw new \LogicException('Entity is missing or not a User');
        }

        // Créer un formulaire pour choisir la date de fin de bannissement
        $form = $this->createFormBuilder()
            ->add('banUntil', DateTimeType::class, [
                'label' => 'Date de fin de bannissement',
                'widget' => 'single_text',
                'data' => new \DateTime('+30 days'),
                'attr' => ['min' => (new \DateTime())->format('Y-m-d\TH:i')]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Bannir temporairement',
                'attr' => ['class' => 'btn btn-danger']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Convertir en DateTimeImmutable
            $banUntil = \DateTimeImmutable::createFromMutable($data['banUntil']);
            
            // Définir la date de bannissement
            $user->setBannedAt($banUntil);
            
            // Sauvegarder les modifications
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'L\'utilisateur a été banni temporairement jusqu\'au %s.',
                $banUntil->format('d/m/Y H:i')
            ));

            return $this->redirect($adminContext->getReferrer());
        }

        // Rendre le formulaire dans un template
        return $this->render('admin/user/temporary_ban.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    public function permanentBanUser(AdminContext $adminContext, Request $request): Response
    {
        $user = $adminContext->getEntity()->getInstance();
        
        if (!$user instanceof User) {
            throw new \LogicException('Entity is missing or not a User');
        }

        // Créer un formulaire de confirmation
        $form = $this->createFormBuilder()
            ->add('confirm', ChoiceType::class, [
                'label' => 'Êtes-vous sûr de vouloir bannir définitivement cet utilisateur ?',
                'choices' => [
                    'Oui, je confirme' => true,
                    'Non, annuler' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Confirmer',
                'attr' => ['class' => 'btn btn-danger']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            if ($data['confirm']) {
                // Modifier l'email avec le format banned@id.del
                $user->setEmail('banned@' . $user->getId() . '.del');
                
                // Vider les informations personnelles
                $user->setName(null);
                $user->setFirstName(null);
                $user->setPhoneNumber(null);
                $user->setStreetNumber(null);
                $user->setStreetName(null);
                $user->setCity(null);
                $user->setZip(null);
                $user->setRegion(null);
                
                // Définir la date de bannissement permanente
                $user->setBannedAt(new \DateTimeImmutable());
                
                // Sauvegarder les modifications
                $this->entityManager->flush();

                $this->addFlash('success', 'L\'utilisateur a été banni définitivement.');
            } else {
                $this->addFlash('info', 'Le bannissement a été annulé.');
            }

            return $this->redirect($adminContext->getReferrer());
        }

        // Rendre le formulaire dans un template
        return $this->render('admin/user/permanent_ban.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    public function unbanUser(AdminContext $adminContext): Response
    {
        $user = $adminContext->getEntity()->getInstance();
        
        if (!$user instanceof User) {
            throw new \LogicException('Entity is missing or not a User');
        }

        // Vérifier si c'est un bannissement définitif (email modifié)
        $isPermaBanned = strpos($user->getEmail(), 'banned@') === 0 && strpos($user->getEmail(), '.del') !== false;
        
        if ($isPermaBanned) {
            $this->addFlash('warning', 'Cet utilisateur a été banni définitivement et ne peut pas être débanni automatiquement. Contactez un administrateur système.');
            return $this->redirect($adminContext->getReferrer());
        }

        // Sinon, c'est un bannissement temporaire, on peut le débannir
        $user->setBannedAt(null);
        $this->entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur a été débanni avec succès.');

        return $this->redirect($adminContext->getReferrer());
    }
}