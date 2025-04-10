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
            // Récupérer toutes les discussions non archivées où l'admin est destinataire
            $discussions = $discussionRepository->findBy(['isArchived' => false], ['createdAt' => 'DESC']);
            
            if (!empty($discussions)) {
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

        // Récupérer ou créer la discussion
        $discussion = $discussionRepository->findOneBy(['reservation' => $lastReservation]);
        if (!$discussion) {
            $discussion = new Discussion();
            $discussion->setReservation($lastReservation);
            $discussion->setCreatedAt(new \DateTimeImmutable());
            $discussion->setIsLocked(false);
            $discussion->setFilesEnabled(true);
            $discussion->setIsArchived(false);
            
            $em->persist($discussion);
            $em->flush();
        }

        // Récupérer les messages et marquer les messages non lus comme lus
        $messages = $messageRepository->findBy(['discussion' => $discussion], ['sentAt' => 'ASC']);

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
            'currentDiscussion' => $discussion,
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

        // Vérifier si la discussion est verrouillée
        if ($discussion->isLocked()) {
            return new JsonResponse(['error' => 'Cette discussion est verrouillée'], Response::HTTP_FORBIDDEN);
        }

        try {
            $message = new Message();
            $message->setContent($content);
            $message->setUser($user);
            $message->setDiscussion($discussion);
            $message->setSentAt(new \DateTimeImmutable());
            $message->setIsRead(false);

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

            // Si aucun ID de discussion n'est fourni, on vérifie tous les messages non lus
            if (!$discussionId) {
                $unreadCount = $messageRepository->countUnreadForUser($user);
                return new JsonResponse([
                    'newMessages' => $unreadCount > 0,
                    'unreadCount' => $unreadCount
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

            $newMessages = $messageRepository->findUnreadByDiscussionForUser($discussion, $user);
            $messages = [];
            
            foreach ($newMessages as $message) {
                $message->setIsRead(true);
                $messages[] = [
                    'content' => $message->getContent(),
                    'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
                ];
            }

            if (!empty($messages)) {
                $em->flush();
            }

            return new JsonResponse([
                'newMessages' => !empty($messages),
                'messages' => $messages
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