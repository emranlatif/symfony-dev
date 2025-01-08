<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function getTransactions($dates = null, $event = null)
    {
        $query = $this->createQueryBuilder('t');

        if ($dates != null && $dates != 'all') $query->andWhere('t.datePayment = :datepayment');
        if ($event != null && $event != 'all') $query->andWhere('t.eventAdvert = :eventadvert');
        if ($dates != null && $dates != 'all') $query->setParameter('datepayment', $dates);
        if ($event != null && $event != 'all') $query->setParameter('eventadvert', $event);

        return $query->getQuery()->getResult();
    }

    public function getDatesTransactions()
    {
        return $this->createQueryBuilder('t')
            ->select('t.datePayment as dates')
            ->getQuery()
            ->getResult();
    }

    public function getAdverts($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.eventAdvert = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
    }
}
