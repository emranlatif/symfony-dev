<?php

namespace App\Repository;

use App\Entity\ViewBigPremiumAdvert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ViewBigPremiumAdvert>
 *
 * @method ViewBigPremiumAdvert|null find($id, $lockMode = null, $lockVersion = null)
 * @method ViewBigPremiumAdvert|null findOneBy(array $criteria, array $orderBy = null)
 * @method ViewBigPremiumAdvert[]    findAll()
 * @method ViewBigPremiumAdvert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ViewBigPremiumAdvertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ViewBigPremiumAdvert::class);
    }

    /**
     *
     *
     */
    public function add(ViewBigPremiumAdvert $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     *
     *
     */
    public function remove(ViewBigPremiumAdvert $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // /**
    //  * @return ViewBigPremiumAdvert[] Returns an array of ViewBigPremiumAdvert objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ViewBigPremiumAdvert
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
