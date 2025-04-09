<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\AnonymizationService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class UserCrudController extends AbstractCrudController
{
    private EntityManagerInterface $entityManager;
    private AnonymizationService $anonymizationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnonymizationService $anonymizationService
    ) {
        $this->entityManager = $entityManager;
        $this->anonymizationService = $anonymizationService;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            EmailField::new('email'),
            TextField::new('name', 'Nom'),
            TextField::new('firstName', 'Prénom'),
            BooleanField::new('isVerified', 'Vérifié'),
            DateTimeField::new('createdAt', 'Date d\'inscription')->hideOnForm(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Utilisateurs')
            ->setEntityLabelInSingular('Utilisateur')
            ->setSearchFields(['email', 'name', 'firstName'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isVerified', 'Vérifié'))
            ->add(DateTimeFilter::new('createdAt', 'Date d\'inscription'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewProfile = Action::new('viewProfile', 'Voir le profil', 'fa fa-user')
            ->linkToCrudAction('viewProfile')
            ->addCssClass('btn btn-info');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewProfile)
            ->add(Crud::PAGE_DETAIL, $viewProfile)
            ->disable(Action::EDIT)
            ->disable(Action::NEW)
            ->disable(Action::DELETE)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action->setLabel('Retour à la liste');
            });
    }

    public function viewProfile(AdminContext $adminContext): Response
    {
        $id = $adminContext->getRequest()->query->get('entityId');
        if (!$id) {
            throw new \RuntimeException('Missing user ID');
        }

        $user = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.reservations', 'r')
            ->leftJoin('r.service', 's')
            ->leftJoin('r.payments', 'p')
            ->leftJoin('r.discussions', 'd')
            ->leftJoin('d.messages', 'm')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        return $this->render('admin/user/profile.html.twig', [
            'user' => $user
        ]);
    }
}
