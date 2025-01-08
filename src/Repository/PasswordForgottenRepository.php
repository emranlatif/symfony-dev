<?php

namespace App\Repository;

use App\Entity\PasswordForgotten;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PasswordForgotten|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasswordForgotten|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasswordForgotten[]    findAll()
 * @method PasswordForgotten[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasswordForgottenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordForgotten::class);
    }


    public function findOneByHash($hash): ?PasswordForgotten
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.hash = :hash')
            ->setParameter('hash', $hash)
            ->andWhere('p.requestDate >= :date')
            ->setParameter('date', new DateTime('-4 hours'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return PasswordForgotten[] Returns an array of PasswordForgotten objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PasswordForgotten
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
