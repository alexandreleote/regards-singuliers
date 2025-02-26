<?php

namespace App\Repository;

use App\Entity\Realisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Realisation>
 *
 * @method Realisation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Realisation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Realisation[]    findAll()
 * @method Realisation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RealisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Realisation::class);
    }
}
