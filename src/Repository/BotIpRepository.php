<?php

namespace App\Repository;

use App\Entity\BotIp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BotIp>
 */
class BotIpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotIp::class);
    }

    /**
     * Vérifie si une IP est déjà enregistrée comme bot
     */
    public function isIpBanned(string $ip): bool
    {
        return $this->count(['ip' => $ip]) > 0;
    }

    /**
     * Trouve les IPs de bots par période
     */
    public function findByPeriod(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.detectedAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('b.detectedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 