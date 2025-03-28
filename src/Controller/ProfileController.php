<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditType;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Service\AnonymizationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\DeleteAccountFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('', name: 'profile')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        $reservations = $reservationRepository->findByUser($user);
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
            'page_title' => 'Mon Profil - regards singuliers',
            'meta_description' => 'Accédez à votre profil sur regards singuliers pour gérer vos informations personnelles, suivre l\'avancement de vos projets et interagir avec notre équipe.',
        ]);
    }

    #[Route('/modification', name: 'profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        /** @var User $user */
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
                        'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
                        'page_title' => 'Modifier mon profil - '.$user->getFullName()
                    ]);
                }

                if ($form->get('newPassword')->getData() !== $form->get('confirmPassword')->getData()) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->render('profile/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                        'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
                        'page_title' => 'Modifier mon profil - '.$user->getFullName()
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
                    'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
                    'page_title' => 'Modifier mon profil - '.$user->getFullName()
                ]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'meta_description' => 'Mettez à jour vos informations personnelles sur regards singuliers pour garder vos coordonnées à jour et faciliter la gestion de vos projets.',
            'page_title' => 'Modifier mon profil - '.$user->getFullName()
        ]);
    }

    #[Route('/reservations', name: 'profile_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        $allReservations = $reservationRepository->findByUser($user);
        
        $currentReservations = [];
        $upcomingReservations = [];
        $pastReservations = [];
        
        $now = new \DateTime();
        
        foreach ($allReservations as $reservation) {
            if ($reservation->getBookedAt() < $now) {
                $pastReservations[] = $reservation;
            } elseif ($reservation->getBookedAt() > $now) {
                $upcomingReservations[] = $reservation;
            } else {
                $currentReservations[] = $reservation;
            }
        }
        
        return $this->render('profile/reservations.html.twig', [
            'currentReservations' => $currentReservations,
            'upcomingReservations' => $upcomingReservations,
            'pastReservations' => $pastReservations,
            'page_title' => 'Mes réservations - regards singuliers',
            'meta_description' => 'Consultez et gérez vos réservations sur regards singuliers, suivez l\'avancement de vos projets et planifiez vos prochaines étapes avec notre équipe.',
        ]);
    }

    #[Route('/supprimer-mon-compte', name: 'profile_delete')]
    public function deleteAccount(
        Request $request,
        Security $security,
        AnonymizationService $anonymisationService,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {

        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour supprimer votre compte.');
        }

        $form = $this->createForm(DeleteAccountFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            
            // Vérifier le mot de passe
            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Mot de passe incorrect');
                return $this->redirectToRoute('profile_delete');
            }

            try {
                // Déconnecter l'utilisateur d'abord
                $security->logout(false);
                $request->getSession()->invalidate();
                
                // Anonymiser les données de l'utilisateur
                $anonymisationService->anonymiseUserData($user);
                
                // Ajouter le message flash
                /* $this->addFlash('success', 'Votre compte a été supprimé avec succès.'); */
                
                // Rediriger vers la page d'accueil
                return $this->redirectToRoute('home');
                
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la suppression du compte: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du compte.');
                return $this->redirectToRoute('profile_delete');
            }
        }

        return $this->render('profile/delete.html.twig', [
            'deleteForm' => $form->createView(),
            'meta_description' => 'Supprimez votre compte et toutes vos données associées de manière sécurisée.',
            'page_title' => 'Supprimer mon compte - regards singuliers'
        ]);
    }
}