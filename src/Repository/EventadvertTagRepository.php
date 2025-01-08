<?php

namespace App\Repository;

use App\Entity\EventadvertTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventadvertTag>
 *
 * @method EventadvertTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventadvertTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventadvertTag[]    findAll()
 * @method EventadvertTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventadvertTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventadvertTag::class);
    }

    /**
     */
    public function add(EventadvertTag $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     */
    public function remove(EventadvertTag $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // /**
    //  * @return EventadvertTag[] Returns an array of EventadvertTag objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EventadvertTag
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
