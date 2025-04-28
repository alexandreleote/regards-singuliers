<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Discussion;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\DiscussionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/discussion')]
final class DiscussionController extends AbstractController
{
    #[Route('', name: 'profile_discussions')]
    #[Route('/{id}', name: 'admin_discussion', requirements: ['id' => '\d+'])]

    public function discussions(
        Request $request,
        DiscussionRepository $discussionRepository, 
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        // Initialiser les variables par défaut
        $discussions = [];
        $messages = [];
        $currentDiscussion = null;
        $hasUnreadMessages = false;

        // Si l'utilisateur est un admin, on récupère toutes les discussions où il est destinataire
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            // Récupérer toutes les discussions non archivées triées par date du dernier message
            $discussions = $discussionRepository->findByLastMessageDate();
            
            if (!empty($discussions)) {
                // Pour chaque discussion, vérifier s'il y a des messages non lus
                foreach ($discussions as $discussion) {
                    $unreadMessages = $messageRepository->findUnreadByDiscussionForUser($discussion, $user);
                    $discussion->hasUnreadMessages = count($unreadMessages) > 0;
                }
                
                // Récupérer la discussion sélectionnée ou la première par défaut
                $discussionId = $request->get('id');
                if ($discussionId !== null) {
                    $currentDiscussion = $discussionRepository->find($discussionId);
                    
                    // Vérifier que l'utilisateur a le droit d'accéder à cette discussion
                    if (!$currentDiscussion || 
                        (!in_array('ROLE_ADMIN', $user->getRoles()) && 
                        $currentDiscussion->getReservation()->getUser()->getId() !== $user->getId())
                    ) {
                        return $this->redirectToRoute('profile_discussions');
                    }
                }
                
                if (!$currentDiscussion) {
                    $currentDiscussion = $discussions[0];
                }

                // Récupérer les messages et marquer les messages non lus comme lus
                $messages = $messageRepository->findBy(['discussion' => $currentDiscussion], ['sentAt' => 'ASC']);

                foreach ($messages as $message) {
                    if (!$message->isRead() && $message->getUser()->getId() !== $user->getId()) {
                        $message->setIsRead(true);
                        $hasUnreadMessages = true;
                    }
                }

                if ($hasUnreadMessages) {
                    $em->flush();
                }
            }

            return $this->render('profile/discussions.html.twig', [
                'messages' => $messages,
                'discussions' => $discussions,
                'currentDiscussion' => $currentDiscussion,
                'hasUnreadMessages' => $hasUnreadMessages,
                'page_title' => 'Messagerie - regards singuliers',
                'meta_description' => 'Profitez de la messagerie pour discuter de votre projet avec votre architecte d\'intérieur.',
            ]);
        }

        // Pour un utilisateur normal
        $lastReservation = $user->getReservations()->last();
        if (!$lastReservation) {
            return $this->render('profile/discussions.html.twig', [
                'messages' => [],
                'discussions' => [],
                'currentDiscussion' => null,
                'hasUnreadMessages' => false,
                'page_title' => 'Messagerie - regards singuliers',
                'meta_description' => 'Profitez de la messagerie pour discuter de votre projet avec votre architecte d\'intérieur.',
            ]);
        }

        // Vérifier s'il existe déjà une discussion active pour cet utilisateur
        $existingDiscussion = $discussionRepository->createQueryBuilder('d')
            ->leftJoin('d.reservation', 'r')
            ->where('d.isArchived = :archived')
            ->andWhere('r.user = :user')
            ->setParameter('archived', false)
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$existingDiscussion) {
            // Si aucune discussion active n'existe, en créer une nouvelle
            $existingDiscussion = new Discussion();
            $existingDiscussion->setReservation($lastReservation);
            $existingDiscussion->setCreatedAt(new \DateTimeImmutable());
            $existingDiscussion->setIsLocked(false);
            $existingDiscussion->setFilesEnabled(true);
            $existingDiscussion->setIsArchived(false);
            
            $em->persist($existingDiscussion);
            $em->flush();
        } else {
            // Si une discussion existe déjà, mettre à jour la réservation associée
            $existingDiscussion->setReservation($lastReservation);
            $em->flush();
        }

        // Récupérer les messages et marquer les messages non lus comme lus
        $messages = $messageRepository->findBy(['discussion' => $existingDiscussion], ['sentAt' => 'ASC']);

        foreach ($messages as $message) {
            if (!$message->isRead() && $message->getUser()->getId() !== $user->getId()) {
                $message->setIsRead(true);
                $hasUnreadMessages = true;
            }
        }

        if ($hasUnreadMessages) {
            $em->flush();
        }
        
        return $this->render('profile/discussions.html.twig', [
            'messages' => $messages,
            'discussions' => [],
            'currentDiscussion' => $existingDiscussion,
            'hasUnreadMessages' => $hasUnreadMessages,
            'page_title' => 'Messagerie - regards singuliers',
            'meta_description' => 'Échangez en direct avec votre architecte d\'intérieur.',
        ]);
    }

    #[Route('/send', name: 'send_message', methods: ['POST'])]
    public function sendMessage(
        Request $request, 
        EntityManagerInterface $em, 
        DiscussionRepository $discussionRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        $content = $request->request->get('content');
        $discussionId = $request->request->get('discussionId');
        
        if (empty($content)) {
            return new JsonResponse(['error' => 'Le message ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        // Pour l'admin, on utilise l'ID de discussion fourni
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $discussion = $discussionRepository->find($discussionId);
        } else {
            // Pour un utilisateur normal, on utilise sa dernière réservation
            $discussion = $discussionRepository->findOneBy(['reservation' => $user->getReservations()->last()]);
            
            // Vérifier que la discussion appartient bien à l'utilisateur
            if ($discussion && $discussion->getReservation()->getUser()->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Vous n\'avez pas accès à cette discussion'], Response::HTTP_FORBIDDEN);
            }
        }

        if (!$discussion) {
            return new JsonResponse(['error' => 'Discussion non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si la discussion est verrouillée ou archivée
        if ($discussion->isLocked()) {
            return new JsonResponse(['error' => 'Cette discussion est verrouillée'], Response::HTTP_FORBIDDEN);
        }

        if ($discussion->isArchived()) {
            return new JsonResponse(['error' => 'Cette discussion est archivée'], Response::HTTP_FORBIDDEN);
        }

        try {
            $message = new Message();
            $message->setContent($content);
            $message->setUser($user);
            $message->setDiscussion($discussion);
            $message->setSentAt(new \DateTimeImmutable());
            
            // Important : le message est déjà lu pour l'expéditeur, mais pas pour le destinataire
            $message->setIsRead(false);
            
            // Débogage
            error_log('New message created by user ID: ' . $user->getId() . ' in discussion ID: ' . $discussion->getId());

            $em->persist($message);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => [
                    'content' => $message->getContent(),
                    'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de l\'envoi du message'], 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/check-new', name: 'check_new_messages', methods: ['GET'])]
    public function checkNewMessages(
        Request $request,
        MessageRepository $messageRepository, 
        DiscussionRepository $discussionRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            
            /** @var User $user */
            $user = $this->getUser();
            $discussionId = $request->query->get('discussionId');

            // Si aucun ID de discussion n'est fourni, on vérifie tous les messages non lus (pour toutes les discussions)
            if (!$discussionId) {
                // Forcer le comptage des messages non lus
                $unreadCount = $messageRepository->countUnreadForUser($user);
                
                // Débogage
                error_log('Check new messages for user ID: ' . $user->getId() . ', unread count: ' . $unreadCount);
                
                // Si on demande juste le nombre de messages non lus
                if ($request->query->get('countOnly') === 'true') {
                    return new JsonResponse([
                        'newMessages' => $unreadCount > 0,
                        'unreadCount' => $unreadCount,
                        'userId' => $user->getId(),
                        'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                    ]);
                }
                
                // Sinon, on récupère les discussions avec des messages non lus
                $discussions = $discussionRepository->findWithUnreadMessages($user);
                $unreadDiscussions = [];
                
                foreach ($discussions as $discussion) {
                    $unreadMessages = $messageRepository->findUnreadByDiscussionForUser($discussion, $user);
                    if (count($unreadMessages) > 0) {
                        $unreadDiscussions[] = [
                            'id' => $discussion->getId(),
                            'title' => $discussion->getTitle() ?: 'Discussion #' . $discussion->getId(),
                            'unreadCount' => count($unreadMessages)
                        ];
                    }
                }
                
                return new JsonResponse([
                    'newMessages' => $unreadCount > 0,
                    'unreadCount' => $unreadCount,
                    'unreadDiscussions' => $unreadDiscussions
                ]);
            }

            // Pour l'admin, on utilise l'ID de discussion fourni
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $discussion = $discussionRepository->find($discussionId);
            } else {
                // Pour un utilisateur normal, on utilise sa dernière réservation
                $discussion = $discussionRepository->findOneBy(['reservation' => $user->getReservations()->last()]);
                
                // Vérifier que la discussion appartient bien à l'utilisateur
                if ($discussion && $discussion->getReservation()->getUser()->getId() !== $user->getId()) {
                    return new JsonResponse(['error' => 'Vous n\'avez pas accès à cette discussion'], Response::HTTP_FORBIDDEN);
                }
            }
            
            if (!$discussion) {
                return new JsonResponse(['error' => 'Discussion non trouvée'], Response::HTTP_NOT_FOUND);
            }

            // Trouver les messages non lus pour cet utilisateur dans cette discussion
            $newMessages = $messageRepository->findUnreadByDiscussionForUser($discussion, $user);
            $hasNewMessages = count($newMessages) > 0;
            $messages = [];
            
            // Préparer les données des messages pour le front-end
            foreach ($newMessages as $message) {
                // Ne pas marquer comme lu immédiatement pour permettre l'affichage de la notification
                // $message->setIsRead(true);
                $messages[] = [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
                    'sender' => $message->getUser()->getUsername(),
                ];
            }

            // Si l'utilisateur a explicitement demandé à marquer comme lu
            if ($request->query->get('markAsRead') === 'true' && !empty($messages)) {
                foreach ($newMessages as $message) {
                    $message->setIsRead(true);
                }
                $em->flush();
            }

            return new JsonResponse([
                'newMessages' => $hasNewMessages,
                'messages' => $messages,
                'count' => count($messages)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la récupération des messages'], 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/toggle-lock/{id}', name: 'toggle_discussion_lock', methods: ['POST'])]
    public function toggleLock(
        Discussion $discussion,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $discussion->setIsLocked(!$discussion->isLocked());
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'isLocked' => $discussion->isLocked()
        ]);
    }

    #[Route('/{id}/delete-message/{messageId}', name: 'user_discussion_delete_message', methods: ['POST'])]
    public function deleteMessage(int $id, int $messageId, EntityManagerInterface $entityManager): Response
    {
        $discussion = $entityManager->getRepository(Discussion::class)->find($id);
        if (!$discussion) {
            throw $this->createNotFoundException('Discussion non trouvée');
        }

        $message = $entityManager->getRepository(Message::class)->find($messageId);
        if (!$message || $message->getDiscussion() !== $discussion) {
            throw $this->createNotFoundException('Message non trouvé');
        }

        if ($message->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce message');
        }

        $message->setIsDeleted(true);
        $entityManager->flush();

        $this->addFlash('success', 'Message supprimé avec succès');
        return $this->redirectToRoute('profile_discussions', ['discussionId' => $id]);
    }
}