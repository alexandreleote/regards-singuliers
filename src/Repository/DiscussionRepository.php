<?php

namespace App\Repository;

use App\Entity\Discussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

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
     * Find recent discussions with their latest messages
     * 
     * @param int $limit Number of discussions to retrieve
     * @return Discussion[]
     */
    public function findRecentDiscussionsWithMessages(int $limit = 3): array
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.messages', 'm', Join::WITH, 'm.discussion = d.id')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
