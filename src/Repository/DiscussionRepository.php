<?php

namespace App\Repository;

use App\Entity\Discussion;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Discussion>
 */
class DiscussionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussion::class);
    }

    /**
     * Trouve les discussions actives d'un utilisateur
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.reservation', 'r')
            ->leftJoin('d.messages', 'm')
            ->addSelect('r', 'm')
            ->andWhere('r.user = :user')
            ->andWhere('d.isArchived = :archived')
            ->setParameter('user', $user)
            ->setParameter('archived', false)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les discussions d'une réservation
     */
    public function findByReservation(Reservation $reservation): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.messages', 'm')
            ->addSelect('m')
            ->andWhere('d.reservation = :reservation')
            ->setParameter('reservation', $reservation)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les discussions avec des messages non lus
     */
    public function findWithUnreadMessages(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.messages', 'm')
            ->leftJoin('d.reservation', 'r')
            ->addSelect('m', 'r')
            ->andWhere('r.user = :user')
            ->andWhere('m.isRead = :isRead')
            ->andWhere('m.user != :user')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les discussions archivées
     */
    public function findArchived(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.isArchived = :archived')
            ->setParameter('archived', true)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.reservation', 'r')
            ->leftJoin('d.messages', 'm')
            ->addSelect('r', 'm')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les discussions triées par date du dernier message
     */
    public function findByLastMessageDate(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.messages', 'm')
            ->leftJoin('d.reservation', 'r')
            ->addSelect('r', 'm')
            ->andWhere('d.isArchived = :archived')
            ->setParameter('archived', false)
            ->orderBy('m.sentAt', 'DESC')
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Discussion[] Returns an array of Discussion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Discussion
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
