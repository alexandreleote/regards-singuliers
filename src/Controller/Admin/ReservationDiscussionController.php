<?php

namespace App\Controller\Admin;

use App\Entity\Discussion;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationDiscussionController extends AbstractController
{
    #[Route('/admin/reservation/{id}/discussion', name: 'admin_reservation_add_discussion', methods: ['POST'])]
    public function addDiscussion(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response {
        $message = $request->request->get('message');
        
        if (empty($message)) {
            $this->addFlash('error', 'Le message ne peut pas être vide.');
            return $this->redirectToRoute('admin_reservation_discussions', ['id' => $reservation->getId()]);
        }

        $discussion = new Discussion();
        $discussion->setReservation($reservation);
        $discussion->setAuthor($this->getUser());
        $discussion->setMessage($message);
        $discussion->setCreatedAt(new \DateTime());

        $entityManager->persist($discussion);
        $entityManager->flush();

        $this->addFlash('success', 'Message ajouté avec succès.');
        return $this->redirectToRoute('admin_reservation_discussions', ['id' => $reservation->getId()]);
    }
} 