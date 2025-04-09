<?php

namespace App\Controller\Admin;

use App\Entity\Discussion;
use App\Entity\Reservation;
use App\Entity\Message;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Routing\Annotation\Route;

class DiscussionCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public static function getEntityFqcn(): string
    {
        return Discussion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Discussion')
            ->setEntityLabelInPlural('Discussions')
            ->setPageTitle('index', 'Discussions avec les utilisateurs')
            ->setPageTitle('detail', fn (Discussion $discussion) => sprintf('Discussion avec %s', 
                $discussion->getReservation()->getUser()->getFullName() ?? $discussion->getReservation()->getUser()->getEmail()
            ))
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureActions(Actions $actions): Actions
    {
        $lockDiscussion = Action::new('lockDiscussion', 'Verrouiller', 'fa fa-lock')
            ->linkToCrudAction('lockDiscussion')
            ->displayIf(static function ($entity) {
                return !$entity->isLocked();
            });

        $unlockDiscussion = Action::new('unlockDiscussion', 'Déverrouiller', 'fa fa-unlock')
            ->linkToCrudAction('unlockDiscussion')
            ->displayIf(static function ($entity) {
                return $entity->isLocked();
            });

        $archiveDiscussion = Action::new('archiveDiscussion', 'Archiver', 'fa fa-archive')
            ->linkToCrudAction('archiveDiscussion')
            ->displayIf(static function ($entity) {
                return !$entity->isArchived();
            });

        $unarchiveDiscussion = Action::new('unarchiveDiscussion', 'Désarchiver', 'fa fa-box-open')
            ->linkToCrudAction('unarchiveDiscussion')
            ->displayIf(static function ($entity) {
                return $entity->isArchived();
            });

        $toggleFiles = Action::new('toggleFiles', 'Autoriser les fichiers', 'fa fa-file')
            ->linkToCrudAction('toggleFiles')
            ->displayIf(static function ($entity) {
                return !$entity->isFilesEnabled();
            });

        $disableFiles = Action::new('disableFiles', 'Interdire les fichiers', 'fa fa-ban')
            ->linkToCrudAction('disableFiles')
            ->displayIf(static function ($entity) {
                return $entity->isFilesEnabled();
            });

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $lockDiscussion)
            ->add(Crud::PAGE_DETAIL, $unlockDiscussion)
            ->add(Crud::PAGE_DETAIL, $archiveDiscussion)
            ->add(Crud::PAGE_DETAIL, $unarchiveDiscussion)
            ->add(Crud::PAGE_DETAIL, $toggleFiles)
            ->add(Crud::PAGE_DETAIL, $disableFiles)
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action->setLabel('Retour');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('reservation', 'Réservation')
                ->setCrudController(ReservationCrudController::class)
                ->hideOnDetail(),
            DateTimeField::new('createdAt', 'Date de création')
                ->hideOnDetail(),
            BooleanField::new('isLocked', 'Verrouillée')
                ->hideOnDetail(),
            BooleanField::new('isArchived', 'Archivée')
                ->hideOnDetail(),
            BooleanField::new('filesEnabled', 'Fichiers autorisés')
                ->hideOnDetail(),
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = CollectionField::new('messages', 'Messages')
                ->setTemplatePath('admin/discussion/messages.html.twig')
                ->onlyOnDetail();
        }

        return $fields;
    }

    #[Route('/admin/discussion/{id}/message', name: 'admin_discussion_add_message', methods: ['POST'])]
    public function addMessage(Request $request, Discussion $discussion): Response
    {
        if ($discussion->isLocked()) {
            $this->addFlash('error', 'La discussion est verrouillée, vous ne pouvez pas ajouter de message.');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $discussion->getId()
            ]);
        }

        $content = $request->request->get('message');
        if (empty($content)) {
            $this->addFlash('error', 'Le message ne peut pas être vide.');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $discussion->getId()
            ]);
        }

        $message = new Message();
        $message->setDiscussion($discussion);
        $message->setUser($this->getUser());
        $message->setContent($content);
        $message->setSentAt(new \DateTimeImmutable());
        $message->setIsRead(false);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->addFlash('success', 'Message envoyé avec succès.');
        
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $discussion->getId()
        ]);
    }

    public function lockDiscussion(Discussion $discussion): Response
    {
        $discussion->setIsLocked(true);
        $this->entityManager->flush();
        $this->addFlash('success', 'La discussion a été verrouillée.');
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $discussion->getId()
        ]);
    }

    public function unlockDiscussion(Discussion $discussion): Response
    {
        $discussion->setIsLocked(false);
        $this->entityManager->flush();
        $this->addFlash('success', 'La discussion a été déverrouillée.');
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $discussion->getId()
        ]);
    }

    public function archiveDiscussion(Discussion $discussion): Response
    {
        $discussion->setIsArchived(true);
        $this->entityManager->flush();
        $this->addFlash('success', 'La discussion a été archivée.');
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $discussion->getId()
        ]);
    }

    public function unarchiveDiscussion(Discussion $discussion): Response
    {
        $discussion->setIsArchived(false);
        $this->entityManager->flush();
        $this->addFlash('success', 'La discussion a été désarchivée.');
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $discussion->getId()
        ]);
    }

    public function toggleFiles(Discussion $discussion): Response
    {
        $discussion->setFilesEnabled(true);
        $this->entityManager->flush();
        $this->addFlash('success', 'Les documents sont maintenant autorisés dans cette discussion.');
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $discussion->getId()
        ]);
    }

    public function disableFiles(Discussion $discussion): Response
    {
        $discussion->setFilesEnabled(false);
        $this->entityManager->flush();
        $this->addFlash('success', 'Les documents ne sont plus autorisés dans cette discussion.');
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $discussion->getId()
        ]);
    }
} 