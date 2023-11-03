<?php

namespace App\Repository;

use App\Entity\Consumer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consumer>
 *
 * @method Consumer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Consumer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Consumer[]    findAll()
 * @method Consumer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsumerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consumer::class);
    }
}
