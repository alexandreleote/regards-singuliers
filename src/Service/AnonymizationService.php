<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\DBAL\LockMode;

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
            // Nettoyer le cache et démarrer une transaction
            $this->entityManager->clear();
            $this->entityManager->getConnection()->beginTransaction();

            // Récupérer l'utilisateur avec un verrouillage pessimiste
            $userId = $user->getId();
            $user = $this->entityManager->find(User::class, $userId, LockMode::PESSIMISTIC_WRITE);
            
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            // Génération d'un identifiant court
            $uniqueId = substr(bin2hex(random_bytes(4)), 0, 8);
            $timestamp = (new \DateTimeImmutable())->format('YmdHis');
            
            // Anonymisation avec des chaînes plus courtes
            $anonymousData = "ANON_{$uniqueId}";
            
            // Mise à jour des données personnelles avec des chaînes plus courtes
            $user
                ->setName($anonymousData)
                ->setFirstName($anonymousData)
                ->setEmail("del_{$userId}_{$timestamp}@anon.com")
                ->setPhoneNumber("00000000") // Numéro fixe court
                ->setStreetNumber("0")
                ->setStreetName($anonymousData)
                ->setCity($anonymousData)
                ->setZip("00000")
                ->setRegion($anonymousData)
                ->setIsVerified(false)
                ->setRoles(['ROLE_DELETED'])
                ->setResetToken(null)
                ->setResetTokenExpiresAt(null)
                ->setDeletedAt(new \DateTimeImmutable())
                ->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));

            // Si l'utilisateur a été banni auparavant
            if ($user->getLastIpHashed() !== null ) {
                // Stockage de l'IP si disponible
                if ($ip = $this->requestStack->getCurrentRequest()?->getClientIp()) {
                    $user->setLastIpHashed(password_hash($ip, PASSWORD_BCRYPT));
                }
            }

            // Persister et flusher les modifications
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            // Nettoyer le cache après la transaction
            $this->entityManager->clear(User::class);

        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->getConnection()->rollBack();
            }
            $this->entityManager->clear();
            
            throw new \Exception('Erreur lors de l\'anonymisation : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Vérifie si une IP est déjà associée à un utilisateur supprimé
     */
    public function isIpBanned(string $ip): bool
    {
        return (bool) $this->entityManager->createQueryBuilder()
            ->select('COUNT(u)')
            ->from(User::class, 'u')
            ->where('u.deletedAt IS NOT NULL')
            ->andWhere('u.lastIpHashed = :ip')
            ->setParameter('ip', password_hash($ip, PASSWORD_BCRYPT))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les utilisateurs supprimés
     */
    public function findDeletedUsers(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.deletedAt IS NOT NULL')
            ->orderBy('u.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}