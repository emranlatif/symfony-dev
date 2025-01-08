<?php

namespace App\Repository;

use App\Entity\Eventadvert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Eventadvert|null find($id, $lockMode = null, $lockVersion = null)
 * @method Eventadvert|null findOneBy(array $criteria, array $orderBy = null)
 * @method Eventadvert[]    findAll()
 * @method Eventadvert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventadvertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eventadvert::class);
    }

    public function getFutureEvents($categories = null, $featured = 0)
    {
        $entityManager = $this->getEntityManager();

        $categories_q = '';
        if ($categories != null) {
            $categories_q = ' AND e.category IN (:categories) ';
        }

        $query = $entityManager->createQuery('SELECT e
            FROM App\Entity\Eventadvert e
            WHERE (e.eventStartDate >= CURRENT_DATE() OR e.eventEndDate >= CURRENT_DATE())' . $categories_q . 'ORDER BY e.eventStartDate ASC');

        if ($categories != null) {
            $query->setParameters(array('categories' => $categories));
        }

        return $query->getResult();
    }

    public function getLatestCreatedEvents($deletedUsers, $date_start_from = null, $date_start_to = null, $date_end_from = null, $date_end_to = null, $lat_long = null, $radius = null, $categories = null)
    {
        $query = $this->createQueryBuilder('e');
        $query->select('e as events');
        $query->where('e.eventStartDate >= CURRENT_DATE() OR e.eventEndDate >= CURRENT_DATE()');

        $query->andWhere('e.deleted IS NULL OR e.deleted = :false');
        $query->setParameter('false', false);

        if (count($deletedUsers) > 0) {
            $query->andWhere('e.userId NOT IN (:deletedUsers)');
            $query->setParameter('deletedUsers', $deletedUsers);
        }

        if ((($date_start_from != null && $date_start_to == null) || ($date_start_to != null && $date_start_from == null)) ||
            (($date_end_from != null && $date_end_to == null) || ($date_end_to != null && $date_end_from == null))
        ) {
            return [];
        }

        if (($radius != null && $lat_long == null) ||
            ($lat_long != null && $radius == null)
        ) {
            return [];
        }

        if (($date_start_from != null && $date_start_to != null) &&
            ($date_end_from == null && $date_end_to == null && $lat_long == null && $radius == null)
        ) {
            $query->andWhere('e.eventStartDate BETWEEN :from AND :to');
            $query->setParameter('from', $date_start_from);
            $query->setParameter('to', $date_start_to);
        }

        if (($date_start_from != null && $date_start_to != null && $date_end_from != null && $date_end_to != null) &&
            ($lat_long == null && $radius == null)
        ) {
            $query->andWhere('e.eventStartDate BETWEEN :start_from AND :start_to');
            $query->andWhere('e.eventEndDate BETWEEN :end_from AND :end_to');

            $query->setParameter('start_from', $date_start_from);
            $query->setParameter('start_to', $date_start_to);
            $query->setParameter('end_from', $date_end_from);
            $query->setParameter('end_to', $date_end_to);
        }

        if (($date_start_from != null && $date_start_to != null && $lat_long != null && $radius != null) &&
            ($date_end_from == null && $date_end_to == null)
        ) {
            $query->andWhere('e.eventStartDate BETWEEN :start_from AND :start_to');
            $distance_qry = "(((acos(sin((" . $lat_long['latitude'] . "*pi()/180)) * sin((g.latitude*pi()/180))+cos((" . $lat_long['latitude'] . "*pi()/180)) * cos((g.latitude*pi()/180)) * cos(((" . $lat_long['longitude'] . "-g.longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344)";
            $query->addSelect($distance_qry . ' AS distance')
                ->join(
                    'App\Entity\Geoplaces',
                    'g',
                    Join::WITH,
                    'e.geoPlacesId = g.id'
                )
                ->having('distance <= ' . $radius);

            $query->setParameter('start_from', $date_start_from);
            $query->setParameter('start_to', $date_start_to);
        }

        if (($date_end_from != null && $date_end_to != null) &&
            ($date_start_from == null && $date_start_to == null && $lat_long == null && $radius == null)
        ) {
            $query->andWhere('e.eventEndDate BETWEEN :end_from AND :end_to');
            $query->setParameter('end_from', $date_end_from);
            $query->setParameter('end_to', $date_end_to);
        }

        if (($date_end_from != null && $date_end_to != null && $lat_long != null && $radius != null) &&
            ($date_start_from == null && $date_start_to == null)
        ) {
            $query->andWhere('e.eventEndDate BETWEEN :end_from AND :end_to');
            $distance_qry = "(((acos(sin((" . $lat_long['latitude'] . "*pi()/180)) * sin((g.latitude*pi()/180))+cos((" . $lat_long['latitude'] . "*pi()/180)) * cos((g.latitude*pi()/180)) * cos(((" . $lat_long['longitude'] . "-g.longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344)";
            $query->addSelect($distance_qry . ' AS distance')
                ->join(
                    'App\Entity\Geoplaces',
                    'g',
                    Join::WITH,
                    'e.geoPlacesId = g.id'
                )
                ->having('distance <= ' . $radius);

            $query->setParameter('end_from', $date_end_from);
            $query->setParameter('end_to', $date_end_to);
        }

        if ($date_start_from != null && $date_start_to != null && $date_end_from != null && $date_end_to != null && $lat_long != null && $radius != null) {
            $query->andWhere('e.eventStartDate BETWEEN :start_from AND :start_to');
            $query->andWhere('e.eventEndDate BETWEEN :end_from AND :end_to');

            $distance_qry = "(((acos(sin((" . $lat_long['latitude'] . "*pi()/180)) * sin((g.latitude*pi()/180))+cos((" . $lat_long['latitude'] . "*pi()/180)) * cos((g.latitude*pi()/180)) * cos(((" . $lat_long['longitude'] . "-g.longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344)";
            $query->addSelect($distance_qry . ' AS distance')
                ->join(
                    'App\Entity\Geoplaces',
                    'g',
                    Join::WITH,
                    'e.geoPlacesId = g.id'
                )
                ->having('distance <= ' . $radius);

            $query->setParameter('start_from', $date_start_from);
            $query->setParameter('start_to', $date_start_to);
            $query->setParameter('end_from', $date_end_from);
            $query->setParameter('end_to', $date_end_to);
        }

        if (
            $lat_long != null && $radius != null && ($date_start_from == null && $date_start_to == null && $date_end_from == null && $date_end_to == null)
        ) {
            $distance_qry = "(((acos(sin((" . $lat_long['latitude'] . "*pi()/180)) * sin((g.latitude*pi()/180))+cos((" . $lat_long['latitude'] . "*pi()/180)) * cos((g.latitude*pi()/180)) * cos(((" . $lat_long['longitude'] . "-g.longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344)";
            $query->addSelect($distance_qry . ' AS distance')
                ->join(
                    'App\Entity\Geoplaces',
                    'g',
                    Join::WITH,
                    'e.geoPlacesId = g.id'
                )
                ->having('distance <= ' . $radius);
        }

        if ($categories != null) {
            $query->andWhere('e.category IN (:categories)');
            $query->setParameter('categories', $categories);

            $query->orWhere('e.subCategory IN (:subCategories)');
            $query->setParameter('subCategories', $categories);
        }

        // Join with the User entity to filter by user status
        $query->innerJoin('e.user', 'u')  // Assuming 'e.user' is the relationship with User entity
            ->andWhere('u.deleted = 0') // User is not deleted
            ->andWhere('u.blocked = 0') // User is not blocked
            ->andWhere('u.enabled = 1');
            
        // show active adverts only
        $query->andWhere('e.status = :activeStatus')->setParameter('activeStatus', 1);

        $query->orderBy('e.creationDate', 'DESC');

        return $query->getQuery()->getResult();
    }

    public function processView($eventId)
    {
        $event = $this->find($eventId);

        $view = (int)$event->getViews();
        $view = $view + 1;
        $event->setViews($view);

        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    public function getEventsPaidInCategories($deletedUsers, $categories)
    {
        $query = $this->createQueryBuilder('e')
            // Main condition for paid events
            ->where('e.paymentStatus = :status')
            ->setParameter('status', 'paid')

            // Premium ads condition (linked with creation date)
            // Filter for premium adverts created after 12/10/2024
            // $query->andWhere('e.premium = :premiumAdvert')
            //     ->setParameter('premiumAdvert', '1')
            //     ->andWhere('e.creationDate	 > :date')
            //     ->setParameter('date', '2024-09-12');

            // Exclude free plans
            ->andWhere('e.plan NOT IN (:freePlans)')
            ->setParameter('freePlans', ['ONE_MONTH_FREE_ADVERT', 'CREDIT_ONE_MONTH_FREE_ADVERT'])

            // Filter by categories
            ->andWhere('e.category IN (:categories)')
            ->setParameter('categories', $categories);

        // Join with the User entity to filter by user status
        $query->innerJoin('e.user', 'u')  // Assuming 'e.user' is the relationship with User entity
            ->andWhere('u.deleted = 0') // User is not deleted
            ->andWhere('u.blocked = 0') // User is not blocked
            ->andWhere('u.enabled = 1'); // User is enabled

        // Execute the query and return the results
        return $query->getQuery()->getResult();
    }



    public function getEventsCreationsDate($type = null, $categories = null)
    {
        $query = $this->createQueryBuilder('e');
        if ($type != null) {
            if ($type == 'start') $query->select('e.eventStartDate as date_start');
            if ($type == 'end') $query->select('e.eventEndDate as date_end');
        }

        $query->where('e.eventStartDate >= CURRENT_DATE() OR e.eventEndDate >= CURRENT_DATE()');

        $query->andWhere('e.deleted IS NULL OR e.deleted = :false');
        $query->setParameter('false', false);

        if ($categories != null) {
            if (is_array($categories)) {
                $query->andWhere('e.category IN (:categories)');
                $query->setParameter('categories', $categories);
            } else {
                $query->andWhere('e.category = :categories');
                $query->setParameter('categories', $categories);
            }
        }

        if ($type != null) {
            if ($type == 'start') $query->orderBy('e.eventStartDate', 'ASC');
            if ($type == 'end') $query->orderBy('e.eventEndDate', 'ASC');
        }

        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Eventadvert[] Returns an array of Eventadvert objects
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
    public function findOneBySomeField($value): ?Eventadvert
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findByLocationRadius(array $lat_long, int $radius = 20)
    {
        $distance_qry = "(((acos(sin((" . $lat_long['latitude'] . "*pi()/180)) * sin((g.latitude*pi()/180))+cos((" . $lat_long['latitude'] . "*pi()/180)) * cos((g.latitude*pi()/180)) * cos(((" . $lat_long['longitude'] . "-g.longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344)";

        return $this->createQueryBuilder('e')
            ->addSelect($distance_qry . ' AS distance')
            ->join(
                'App\Entity\Geoplaces',
                'g',
                Join::WITH,
                'e.geoPlacesId = g.id'
            )
            ->andWhere('e.eventStartDate >= CURRENT_DATE() OR e.eventEndDate >= CURRENT_DATE()')
            ->having('distance <= ' . $radius)
            ->orderBy('distance', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCompanyAllActiveAdverts($company)
    {
        $query = $this->createQueryBuilder('e');
        $query->where('e.eventStartDate >= CURRENT_DATE() OR e.eventEndDate >= CURRENT_DATE()');
        $query->andWhere('e.company = :company');
        $query->setParameter('company', $company);

        return $query->getQuery()->getResult();
    }

    public function getPaidAdvertWithDatePaid($deletedUsers = [])
    {
        $query = $this->createQueryBuilder('e');
        $query->where('e.paymentStatus = :status');
        $query->andWhere('e.paidDate IS NOT NULL');
        $query->setParameter('status', 'paid');

        if (count($deletedUsers) > 0) {
            $query->andWhere('e.userId NOT IN (:deletedUsers)');
            $query->setParameter('deletedUsers', $deletedUsers);
        }

        return $query->getQuery()->getResult();
    }

    public function findUserActiveAdverts($user)
    {
        $query = $this->createQueryBuilder('e');
        $query->where('(e.eventStartDate >= CURRENT_DATE() OR e.eventEndDate >= CURRENT_DATE()) and e.status = :one');
        $query->andWhere('e.userId = :user');
        $query->setParameter('user', $user);
        $query->setParameter('one', 1);
        $query->orderBy('e.id', 'DESC');

        return $query->getQuery()->getResult();
    }

    public function findActiveUsersPremiumAdverts(array $deletedUsers)
    {
        $query = $this->createQueryBuilder('e');
        $query->where('e.paymentStatus = :status');
        $query->setParameter('status', 'paid');

        if (count($deletedUsers) > 0) {
            $query->andWhere('e.userId NOT IN (:deletedUsers)');
            $query->setParameter('deletedUsers', $deletedUsers);
        }

        // Premium ads condition (linked with creation date)
        // Filter for premium adverts created after 12/10/2024

        // $query->andWhere('e.creationDate > :date ')
        //     ->setParameter('date', '2024-10-12')
        //     ->andWhere('e.premium = :premiumAdvert')
        //     ->setParameter('premiumAdvert', '1');

        // ignore free ads
        $query->andWhere('e.plan NOT IN (:freePlans)')->setParameter('freePlans', ['ONE_MONTH_FREE_ADVERT', 'CREDIT_ONE_MONTH_FREE_ADVERT']);

        $query->orderBy('e.id', 'DESC');

        return $query->getQuery()->getResult();
    }

    public function getActiveUsersMostViewedAdverts($deletedUsers)
    {
        $query = $this->createQueryBuilder('e');

        if (count($deletedUsers) > 0) {
            $query->where('e.userId NOT IN (:deletedUsers)');
            $query->setParameter('deletedUsers', $deletedUsers);
        }

        $query->orderBy('e.views', 'DESC');
        $query->setMaxResults(5);

        return $query->getQuery()->getResult();
    }

    public function findUserInActiveAdverts(UserInterface $user)
    {
        $query = $this->createQueryBuilder('e');
        $query->where('e.status = :zero');
        $query->andWhere('e.userId = :user');
        $query->setParameter('user', $user);
        $query->setParameter('zero', 0);
        $query->orderBy('e.id', 'DESC');

        return $query->getQuery()->getResult();
    }

    public function isPremiumPlan($paymentPlan)
    {
        // Define non-premium plans
        $nonPremiumPlans = [
            'CREDIT_ONE_MONTH_FREE_ADVERT',
            'ONE_MONTH_FREE_ADVERT_FEE'
        ];

        // Check if the plan is not in the non-premium list
        if (!in_array($paymentPlan, $nonPremiumPlans)) {
            return true;  // Premium plan
        }

        return false;  // Non-premium plan
    }
}
