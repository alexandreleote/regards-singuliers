<?php

namespace App\Repository;

use App\Entity\Blog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blog>
 *
 * @method Blog|null find($id, $lockMode = null, $lockVersion = null)
 * @method Blog|null findOneBy(array $criteria, array $orderBy = null)
 * @method Blog[]    findAll()
 * @method Blog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blog::class);
    }

    /**
     * Custom query methods can be added here if needed
     * For example:
     * public function findLatestPosts($limit = 5)
     * {
     *     return $this->createQueryBuilder('b')
     *         ->orderBy('b.createdAt', 'DESC')
     *         ->setMaxResults($limit)
     *         ->getQuery()
     *         ->getResult()
     * }
     */
}
