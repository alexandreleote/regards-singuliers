<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AnonymizationService
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * Anonymise les données de l'utilisateur
     */
    public function anonymiseUserData(User $user): void
    {

        /** @var User $user  */
        // Génération d'un mot de passe aléatoire sécurisé avec random_bytes
        $password = bin2hex(random_bytes(16));  // 16 octets = 32 caractères hexadécimaux

        // Application de SHA-256 pour anonymiser les autres champs
        $user->setFirstName($this->hashSensitiveData('Anonyme'));
        $user->setName($this->hashSensitiveData('Anonyme'));
        $user->setEmail($this->hashSensitiveData('anonyme' . $user->getId() . '@example.com'));  // Email anonyme
        $user->setPassword($password);  // Nouveau mot de passe aléatoire généré
        $user->setIsVerified(false);  // Désactiver le compte

        // Anonymisation des autres données dans les entités liées
        $user->setStreetNumber($this->hashSensitiveData('Adresse anonymisée'));
        $user->setStreetName($this->hashSensitiveData('Adresse anonymisée'));
        $user->setCity($this->hashSensitiveData('Adresse anonymisée'));
        $user->setZip($this->hashSensitiveData('Adresse anonymisée'));
        $user->setRegion($this->hashSensitiveData('Adresse anonymisée'));
        $user->setPhoneNumber($this->hashSensitiveData('00 00 00 00 00'));

        // Sauvegarde des changements dans la base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Anonymise les données d'un utilisateur banni
     */
    /* public function anonymiseBannedUser(User $user)
    {
        $this->anonymiseUserData($user);
        $user->setIsBanned(true);  // Marque l'utilisateur comme banni

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
    */

    /**
     * Fonction utilitaire pour appliquer SHA-256 aux données sensibles
     */
    private function hashSensitiveData(string $data): string
    {
        return hash('sha256', $data);  // Applique SHA-256 au champ
    }
}