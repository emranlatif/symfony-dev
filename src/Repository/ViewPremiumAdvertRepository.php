<?php

namespace App\Repository;

use App\Entity\ViewPremiumAdvert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ViewPremiumAdvert>
 *
 * @method ViewPremiumAdvert|null find($id, $lockMode = null, $lockVersion = null)
 * @method ViewPremiumAdvert|null findOneBy(array $criteria, array $orderBy = null)
 * @method ViewPremiumAdvert[]    findAll()
 * @method ViewPremiumAdvert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ViewPremiumAdvertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ViewPremiumAdvert::class);
    }

    /**
     *
     *
     */
    public function add(ViewPremiumAdvert $entity, bool $flush = true): void
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
    public function remove(ViewPremiumAdvert $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // /**
    //  * @return ViewPremiumAdvert[] Returns an array of ViewPremiumAdvert objects
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
    public function findOneBySomeField($value): ?ViewPremiumAdvert
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
