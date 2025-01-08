<?php

namespace App\Repository;

use App\Entity\CompanyTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CompanyTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyTag[]    findAll()
 * @method CompanyTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyTag::class);
    }

    // /**
    //  * @return CompanyTag[] Returns an array of CompanyTag objects
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
    public function findOneBySomeField($value): ?CompanyTag
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
