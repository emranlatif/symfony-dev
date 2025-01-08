<?php

namespace App\Repository;

use App\Entity\EventadvertPremium;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventadvertPremium|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventadvertPremium|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventadvertPremium[]    findAll()
 * @method EventadvertPremium[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventadvertPremiumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventadvertPremium::class);
    }

    public function getPaidEvents()
    {
        $query = $this->createQueryBuilder('e');
        $query->where('e.paid = 1');
        $query->orderBy('RAND()');
        return $query->getQuery()->getResult();
    }
}