<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function findBySlug(string $slug): ?Service
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * Trouve les services actifs
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des services par mot-clé
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.reference LIKE :keyword OR s.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('s.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les services par plage de prix
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.price BETWEEN :minPrice AND :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('s.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les services avec leurs réservations
     */
    public function findWithReservations(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.reservations', 'r')
            ->addSelect('r')
            ->orderBy('s.reference', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les 3 prestations les plus réservées
     */
    public function findMostBooked(int $limit = 3): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.reservations', 'r')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('s.id')
            ->orderBy('COUNT(r.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Service[] Returns an array of Service objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Service
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
