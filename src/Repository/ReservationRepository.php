<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Trouve les réservations d'un utilisateur avec leurs relations
     */
    public function findByUserWithRelations(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.service', 's')
            ->leftJoin('r.discussions', 'd')
            ->leftJoin('r.payments', 'p')
            ->addSelect('s', 'd', 'p')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.bookedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations pour un service spécifique
     */
    public function findByService(Service $service): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.service = :service')
            ->setParameter('service', $service)
            ->orderBy('r.bookedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.bookedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations entre deux dates
     */
    public function findBetweenDates(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.bookedAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('r.bookedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations avec paiement en attente
     */
    public function findPendingPayments(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.payments', 'p')
            ->andWhere('r.status = :status')
            ->setParameter('status', 'en attente')
            ->andWhere('r.stripePaymentIntentId IS NOT NULL')
            ->orderBy('r.bookedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une réservation par son ID Stripe Payment Intent
     */
    public function findByStripePaymentIntentId(string $paymentIntentId): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.stripePaymentIntentId = :paymentIntentId')
            ->setParameter('paymentIntentId', $paymentIntentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
