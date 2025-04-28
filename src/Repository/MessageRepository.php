<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Discussion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Trouve les messages d'une discussion avec leurs relations
     */
    public function findByDiscussionWithRelations(Discussion $discussion): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->andWhere('m.discussion = :discussion')
            ->setParameter('discussion', $discussion)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages non lus d'une discussion
     */
    public function findUnreadByDiscussion(Discussion $discussion): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.discussion = :discussion')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('discussion', $discussion)
            ->setParameter('isRead', false)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages non lus d'une discussion pour un utilisateur spécifique
     */
    public function findUnreadByDiscussionForUser(Discussion $discussion, User $user): array
    {
        try {
            // Débogage
            error_log('Finding unread messages for user ID: ' . $user->getId() . ' in discussion ID: ' . $discussion->getId());
            
            $qb = $this->createQueryBuilder('m')
                ->andWhere('m.discussion = :discussion')
                ->andWhere('m.isRead = :isRead')
                ->andWhere('m.user != :user')
                ->setParameter('discussion', $discussion)
                ->setParameter('isRead', false)
                ->setParameter('user', $user)
                ->orderBy('m.sentAt', 'ASC');
            
            // Ajouter une condition spécifique pour l'administrateur
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $qb->leftJoin('m.user', 'u')
                   ->andWhere('u.roles NOT LIKE :role')
                   ->setParameter('role', '%ROLE_ADMIN%');
            }
            
            $result = $qb->getQuery()->getResult();
            
            // Débogage
            error_log('Found ' . count($result) . ' unread messages');
            
            return $result;
        } catch (\Exception $e) {
            error_log('Error finding unread messages: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte les messages non lus pour un utilisateur
     */
    public function countUnreadForUser(User $user): int
    {
        try {
            // Débogage : afficher l'utilisateur pour lequel on compte les messages
            error_log('Counting unread messages for user ID: ' . $user->getId());
            
            // Pour les utilisateurs normaux, on vérifie les messages dans leur discussion
            if (!in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->createQueryBuilder('m')
                    ->select('COUNT(m.id)')
                    ->leftJoin('m.discussion', 'd')
                    ->leftJoin('d.reservation', 'r')
                    ->andWhere('r.user = :user')
                    ->andWhere('m.isRead = :isRead')
                    ->andWhere('m.user != :user')
                    ->setParameter('user', $user)
                    ->setParameter('isRead', false)
                    ->getQuery()
                    ->getSingleScalarResult();
            } else {
                // Pour l'admin, on vérifie tous les messages qui lui sont adressés
                // (messages où l'expéditeur n'est pas admin et où le message n'est pas lu)
                return $this->createQueryBuilder('m')
                    ->select('COUNT(m.id)')
                    ->leftJoin('m.discussion', 'd')
                    ->leftJoin('m.user', 'u')
                    ->andWhere('m.isRead = :isRead')
                    ->andWhere('m.user != :user')
                    ->andWhere('u.roles NOT LIKE :role')
                    ->setParameter('user', $user)
                    ->setParameter('isRead', false)
                    ->setParameter('role', '%ROLE_ADMIN%')
                    ->getQuery()
                    ->getSingleScalarResult();
            }
        } catch (\Exception $e) {
            // En cas d'erreur, retourner 0
            error_log('Error counting unread messages: ' . $e->getMessage());
            return 0;
        }
    }

    //    /**
    //     * @return Message[] Returns an array of Message objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Message
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
