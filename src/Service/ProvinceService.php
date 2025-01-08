<?php

namespace App\Service;

use App\Entity\GeoRegions;
use Doctrine\ORM\EntityManagerInterface;

class ProvinceService
{

    public function __construct(
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function get()
    {
        $provinces = $this->em->getRepository(GeoRegions::class)->findBy([
            'iso' => 'BE',
            'level' => 2,
            'language' => 'NL'
        ], [
            'regionLanguage' => 'DESC',
            'name' => 'ASC'
        ]);


        return $provinces;
    }
}
