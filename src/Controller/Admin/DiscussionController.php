<?php

namespace App\Controller\Admin;

use App\Entity\Discussion;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscussionController extends AbstractController
{
    #[Route('/admin/discussion/{id}/delete-message/{messageId}', name: 'admin_discussion_delete_message', methods: ['POST'])]
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
        return $this->redirectToRoute('admin_discussion_show', ['id' => $id]);
    }
} 