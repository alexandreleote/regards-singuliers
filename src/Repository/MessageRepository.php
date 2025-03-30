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
            ->orderBy('m.createdAt', 'ASC')
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
            ->orderBy('m.createdAt', 'DESC')
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
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages non lus d'une discussion pour un utilisateur spécifique
     */
    public function findUnreadByDiscussionForUser(Discussion $discussion, User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.discussion = :discussion')
            ->andWhere('m.isRead = :isRead')
            ->andWhere('m.user != :user')
            ->setParameter('discussion', $discussion)
            ->setParameter('isRead', false)
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les messages non lus pour un utilisateur
     */
    public function countUnreadForUser(User $user): int
    {
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
