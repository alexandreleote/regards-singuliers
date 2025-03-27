<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AnonymizationService
{
    private $entityManager;
    private $security;
    private $requestStack;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager, 
        Security $security,
        RequestStack $requestStack,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Anonymise les données de l'utilisateur
     */
    public function anonymiseUserData(User $user): void
    {
        try {
            // On commence une transaction
            $this->entityManager->beginTransaction();

            // Récupération de l'utilisateur depuis la base de données pour s'assurer d'avoir l'entité à jour
            $user = $this->entityManager->find(User::class, $user->getId());
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            // Désactivation du compte
            $user->setIsVerified(false);
            $user->setRoles(['ROLE_DELETED']);
            
            // Suppression des tokens de réinitialisation
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            
            // Génération d'un identifiant unique pour l'anonymisation
            $uniqueId = uniqid('deleted_', true);
            
            // Génération d'un mot de passe aléatoire sécurisé
            $randomPassword = bin2hex(random_bytes(32));
            
            // Hashage des données personnelles
            $nameHash = hash('sha256', $uniqueId . '_name');
            $firstNameHash = hash('sha256', $uniqueId . '_firstname');
            $phoneHash = hash('sha256', $uniqueId . '_phone');
            $addressHash = hash('sha256', $uniqueId . '_address');
            
            // Anonymisation des données personnelles
            $user->setName($nameHash);
            $user->setFirstName($firstNameHash);
            $user->setEmail('deleted_' . $user->getId() . '@anonymous.com');
            $user->setPhoneNumber($phoneHash);
            
            // Anonymisation des données d'adresse
            $user->setStreetNumber($addressHash);
            $user->setStreetName($addressHash);
            $user->setCity($addressHash);
            $user->setZip($addressHash);
            $user->setRegion($addressHash);
            
            // Hashage du nouveau mot de passe aléatoire
            $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
            $user->setPassword($hashedPassword);
            
            // Stockage de l'IP hachée
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $ip = $request->getClientIp();
                if ($ip) {
                    $hashedIp = password_hash($ip, PASSWORD_BCRYPT);
                    $user->setLastIpHashed($hashedIp);
                }
            }
            
            // Définition de la date de suppression
            $user->setDeletedAt(new \DateTimeImmutable());
            
            // Forcer la persistance et le flush
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Valider la transaction
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            // En cas d'erreur, on annule la transaction
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            throw new \Exception('Erreur lors de l\'anonymisation des données : ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si une IP est déjà associée à un utilisateur banni
     */
    public function isIpBanned(string $ip): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(u)')
           ->from(User::class, 'u')
           ->where('u.deletedAt IS NOT NULL')
           ->andWhere(
               $qb->expr()->orX(
                   $qb->expr()->eq('u.lastIpHashed', ':ip1'),
                   $qb->expr()->eq('BINARY(u.lastIpHashed)', ':ip2')
               )
           )
           ->setParameter('ip1', password_hash($ip, PASSWORD_BCRYPT))
           ->setParameter('ip2', password_hash($ip, PASSWORD_BCRYPT));

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Récupère tous les utilisateurs supprimés
     */
    public function findDeletedUsers(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        return $qb->select('u')
                 ->from(User::class, 'u')
                 ->where('u.deletedAt IS NOT NULL')
                 ->orderBy('u.deletedAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Vérifie si un email a déjà été utilisé par un compte supprimé
     */
    public function isEmailPreviouslyDeleted(string $email): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $count = $qb->select('COUNT(u)')
                    ->from(User::class, 'u')
                    ->where('u.deletedAt IS NOT NULL')
                    ->andWhere('u.email LIKE :email')
                    ->setParameter('email', '%' . $email . '%')
                    ->getQuery()
                    ->getSingleScalarResult();

        return $count > 0;
    }
}