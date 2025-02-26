<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à votre profil.');
        }

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'profile_edit')]
    public function editProfile(
        Request $request, 
        EntityManagerInterface $entityManager, 
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour modifier votre profil.');
        }

        // Créer un formulaire d'édition de profil si nécessaire
        // Pour l'instant, on laisse un message
        $this->addFlash('info', 'Fonctionnalité d\'édition de profil à venir.');

        return $this->redirectToRoute('profile');
    }

    #[Route('/profile/delete', name: 'profile_delete', methods: ['POST'])]
    public function deleteProfile(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour supprimer votre profil.');
        }

        // Déconnexion manuelle
        $tokenStorage->setToken(null);

        // Suppression du compte
        $entityManager->remove($user);
        $entityManager->flush();

        // Message flash et redirection
        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');

        return $this->redirectToRoute('home');
    }
}
