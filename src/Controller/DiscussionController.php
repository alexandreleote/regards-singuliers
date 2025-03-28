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
    public function discussions(
        DiscussionRepository $discussionRepository, 
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur est un admin, on récupère toutes les discussions non archivées
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $discussions = $discussionRepository->findBy(['isArchived' => false], ['createdAt' => 'DESC']);
            if (empty($discussions)) {
                return $this->render('profile/discussions.html.twig', [
                    'messages' => [],
                    'page_title' => 'Messagerie - regards singuliers',
                    'meta_description' => 'Gérez toutes les discussions avec vos clients.',
                ]);
            }
            
            // Pour l'admin, on prend la première discussion active par défaut
            $currentDiscussion = $discussions[0];
            return $this->render('profile/discussions.html.twig', [
                'messages' => $messageRepository->findBy(['discussion' => $currentDiscussion], ['sentAt' => 'ASC']),
                'discussions' => $discussions,
                'currentDiscussion' => $currentDiscussion,
                'page_title' => 'Messagerie - regards singuliers',
                'meta_description' => 'Gérez toutes les discussions avec vos clients.',
            ]);
        }

        // Pour un utilisateur normal
        $lastReservation = $user->getReservations()->last();
        if (!$lastReservation) {
            return $this->render('profile/discussions.html.twig', [
                'messages' => [],
                'page_title' => 'Messagerie - regards singuliers',
                'meta_description' => 'Échangez en direct avec votre architecte d\'intérieur.',
            ]);
        }

        // Récupérer ou créer la discussion
        $discussion = $discussionRepository->findOneBy(['reservation' => $lastReservation]);
        if (!$discussion) {
            // Récupérer l'admin
            /** @var User|null $admin */
            $admin = $userRepository->findOneBy(['email' => 'admin@regards-singuliers.fr']);
            if (!$admin) {
                throw new \Exception('L\'administrateur n\'a pas été trouvé');
            }

            $discussion = new Discussion();
            $discussion->setReservation($lastReservation);
            $discussion->setCreatedAt(new \DateTimeImmutable());
            $discussion->setIsLocked(false);
            $discussion->setFilesEnabled(true);
            $discussion->setIsArchived(false);
            
            $welcomeMessage = new Message();
            $welcomeMessage->setContent('Bonjour ! Je suis ravie de commencer cette collaboration avec vous. N\'hésitez pas à me poser toutes vos questions concernant votre projet.');
            $welcomeMessage->setUser($admin);
            $welcomeMessage->setDiscussion($discussion);
            $welcomeMessage->setSentAt(new \DateTimeImmutable());
            $welcomeMessage->setIsRead(false);

            $em->persist($discussion);
            $em->persist($welcomeMessage);
            $em->flush();
        }
        
        return $this->render('profile/discussions.html.twig', [
            'messages' => $messageRepository->findBy(['discussion' => $discussion], ['sentAt' => 'ASC']),
            'currentDiscussion' => $discussion,
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
        }

        if (!$discussion) {
            return new JsonResponse(['error' => 'Discussion non trouvée'], Response::HTTP_NOT_FOUND);
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        $discussionId = $request->query->get('discussionId');

        // Pour l'admin, on utilise l'ID de discussion fourni
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $discussion = $discussionRepository->find($discussionId);
        } else {
            // Pour un utilisateur normal, on utilise sa dernière réservation
            $discussion = $discussionRepository->findOneBy(['reservation' => $user->getReservations()->last()]);
        }
        
        if (!$discussion) {
            return new JsonResponse(['error' => 'Discussion non trouvée'], Response::HTTP_NOT_FOUND);
        }

        try {
            $newMessages = $messageRepository->findNewMessages($discussion, $user);
            $messages = [];
            
            foreach ($newMessages as $message) {
                $message->setIsRead(true);
                $messages[] = [
                    'content' => $message->getContent(),
                    'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
                ];
            }

            if (!empty($newMessages)) {
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
}