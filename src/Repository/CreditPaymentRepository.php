<?php

namespace App\Repository;

use App\Entity\CreditPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditPayment>
 *
 * @method CreditPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreditPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreditPayment[]    findAll()
 * @method CreditPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditPayment::class);
    }

    /**
     *
     *
     */
    public function add(CreditPayment $entity, bool $flush = true): void
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
    public function remove(CreditPayment $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // /**
    //  * @return CreditPayment[] Returns an array of CreditPayment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CreditPayment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
