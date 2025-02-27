<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Custom query methods can be added here if needed
     * For example:
     * public function findByDiscussion($discussionId)
     * {
     *     return $this->createQueryBuilder('m')
     *         ->andWhere('m.discussion = :discussionId')
     *         ->setParameter('discussionId', $discussionId)
     *         ->orderBy('m.createdAt', 'ASC')
     *         ->getQuery()
     *         ->getResult()
     * }
     */
}
