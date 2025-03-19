<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\DiscussionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        $reservations = $reservationRepository->findByUser($user);
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(): Response
    {
        return $this->render('profile/edit.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/reservations', name: 'app_profile_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        $reservations = $reservationRepository->findByUser($user);
        
        return $this->render('profile/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/discussions', name: 'app_profile_discussions')]
    public function discussions(DiscussionRepository $discussionRepository): Response
    {
        $user = $this->getUser();
        
        $discussions = $discussionRepository->findByUser($user);
        
        return $this->render('profile/discussions.html.twig', [
            'discussions' => $discussions,
        ]);
    }
} 