<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditType;
use App\Repository\ReservationRepository;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
            'page_title' => 'Mon Profil - regards singuliers',
            'meta_description' => 'Mon Profil - regards singuliers',
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du mot de passe
            if (!empty($form->get('currentPassword')->getData())) {
                if (!$passwordHasher->isPasswordValid($user, $form->get('currentPassword')->getData())) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->render('profile/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                        'meta_description' => 'Modifiez vos informations personnelles et votre mot de passe',
                        'page_title' => 'Modifier mon profil'
                    ]);
                }

                if ($form->get('newPassword')->getData() !== $form->get('confirmPassword')->getData()) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->render('profile/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                        'meta_description' => 'Modifiez vos informations personnelles et votre mot de passe',
                        'page_title' => 'Modifier mon profil'
                    ]);
                }

                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('newPassword')->getData()
                    )
                );
            }

            // Validation de l'entité
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('profile/edit.html.twig', [
                    'form' => $form->createView(),
                    'user' => $user,
                    'meta_description' => 'Modifiez vos informations personnelles et votre mot de passe',
                    'page_title' => 'Modifier mon profil'
                ]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'meta_description' => 'Modifiez vos informations personnelles et votre mot de passe',
            'page_title' => 'Modifier mon profil'
        ]);
    }

    #[Route('/reservations', name: 'app_profile_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        $reservations = $reservationRepository->findByUser($user);
        
        return $this->render('profile/reservations.html.twig', [
            'reservations' => $reservations,
            'page_title' => 'Mes réservations - regards singuliers',
            'meta_description' => 'Mes réservations - regards singuliers',
        ]);
    }

    #[Route('/discussions', name: 'app_profile_discussions')]
    public function discussions(DiscussionRepository $discussionRepository): Response
    {
        $user = $this->getUser();
        
        $discussions = $discussionRepository->findByUser($user);
        
        return $this->render('profile/discussions.html.twig', [
            'discussions' => $discussions,
            'page_title' => 'Mes discussions - regards singuliers',
            'meta_description' => 'Mes discussions - regards singuliers',
        ]);
    }
} 