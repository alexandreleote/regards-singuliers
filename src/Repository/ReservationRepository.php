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

    public function findByUserAndAppointmentDate(User $user, string $filter): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->andWhere('r.status != :status')
            ->setParameter('status', 'annulée');
            
        $now = new \DateTime();
        
        switch ($filter) {
            case 'past':
                $qb->andWhere('r.appointment_datetime < :today')
                   ->setParameter('today', $now->format('Y-m-d 00:00:00'));
                break;
            case 'today':
                $qb->andWhere('r.appointment_datetime >= :todayStart')
                   ->andWhere('r.appointment_datetime <= :todayEnd')
                   ->setParameter('todayStart', $now->format('Y-m-d 00:00:00'))
                   ->setParameter('todayEnd', $now->format('Y-m-d 23:59:59'));
                break;
            case 'upcoming':
                $qb->andWhere('r.appointment_datetime > :now')
                   ->setParameter('now', $now);
                break;
        }
        
        return $qb->orderBy('r.appointment_datetime', $filter === 'past' ? 'DESC' : 'ASC')
                 ->getQuery()
                 ->getResult();
    }

    public function findByCalendlyEventId(string $eventId): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.calendlyEventId = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCalendlyInviteeId(string $inviteeId): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.calendlyInviteeId = :inviteeId')
            ->setParameter('inviteeId', $inviteeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

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
