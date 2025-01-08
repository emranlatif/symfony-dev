<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Eventadvert;
use App\Entity\GeoPlaces;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GeoPlaces|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeoPlaces|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeoPlaces[]    findAll()
 * @method GeoPlaces[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeoPlacesRepository extends ServiceEntityRepository
{
    private $companyRepository;

    public function __construct(ManagerRegistry $registry, CompanyRepository $companyRepository)
    {
        parent::__construct($registry, GeoPlaces::class);

        $this->companyRepository = $companyRepository;
    }

    public function findByIdAndLocale($id, $locale)
    {
        switch ($locale) {
            case 'en':
                $locale = 'nl';
                break;
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.language = :locale')
            ->setParameter('id', $id)
            ->setParameter('locale', strtoupper($locale))
            ->getQuery()
            ->getResult();
    }


    public function findByPostcodeOrLocality($locale, $iso, $value)
    {
        switch ($locale) {
            case 'en':
                $locale = 'nl';
                break;
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.language = :locale')
            ->andWhere('c.iso = :iso')
            ->andWhere('c.locality LIKE :val OR c.postcode LIKE :val')
            ->setParameter('locale', strtoupper($locale))
            ->setParameter('iso', $iso)
            ->setParameter('val', '%' . $value . '%')
            ->orderBy('c.locality', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findActiveCities($locale, $iso, $iso2)
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.language = :locale')
            ->andWhere('g.iso = :iso')
            ->andWhere('g.iso2 = :iso2')
            ->join(
                'App\Entity\Company',
                'c',
                Join::WITH,
                'c.geoPlacesId = g.id'
            )
            ->setParameter('locale', strtoupper($locale))
            ->setParameter('iso', $iso)
            ->setParameter('iso2', $iso2)
            ->orderBy('g.locality', 'ASC')
            ->groupBy('g.locality', 'g.id')
            ->getQuery();

        return $qb->getResult();

    }

    public function findByIdsAndLocale($ids, $locale)
    {
        switch ($locale) {
            case 'en':
                $locale = 'nl';
                break;
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.id IN (:ids)')
            ->andWhere('c.language = :locale')
            ->setParameter('ids', $ids)
            ->setParameter('locale', strtoupper($locale))
            ->getQuery()
            ->getResult();
    }

    public function getByEvents($events, $locale)
    {
        $geoPlacesIds = [];
        /** @var Eventadvert $e */
        foreach ($events as $e) {
            if ($e->getGeoPlacesId() > 0) {
                $geo = $e->getGeoPlacesId();
                $geoPlacesIds[$geo] = $geo;
            } else {
                /** @var Company $company */
                $company = $this->companyRepository->findOneBy(['userId' => $e->getUserId()]);
                $geo = $company?->getGeoPlacesId();
                $geoPlacesIds[$geo] = $geo;
            }
        }

        return $this->findByIdsAndLocale($geoPlacesIds, $locale);

    }

    public function getByEventsArr($events, $locale)
    {
        $geoPlacesIds = [];
        /** @var Eventadvert $e */
        foreach ($events as $e) {
            if ($e[0]->getGeoPlacesId() > 0) {
                $geo = $e[0]->getGeoPlacesId();
                $geoPlacesIds[$geo] = $geo;
            } else {
                /** @var Company $company */
                $company = $this->companyRepository->findOneBy(['userId' => $e[0]->getUserId()]);
                $geo = $company->getGeoPlacesId();
                $geoPlacesIds[$geo] = $geo;
            }
        }

        $geoPlaces = $this->findByIdsAndLocale($geoPlacesIds, $locale);

    }
}
