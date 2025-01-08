<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\GeoPlaces;
use App\Entity\GeoRegions;
use App\Repository\TagRepository;
use App\Repository\GeoPlacesRepository;
use App\Repository\GeoRegionsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxController extends AbstractController
{
    #[Route(path: ['en' => '/ajax/city/   ', 'nl' => '/ajax/city/', 'fr' => '/ajax/city/'], name: 'ajax_city')]
    public function city(Request $request, GeoPlacesRepository $geoPlacesRepository)
    {
        $country = strtoupper($this->getParameter('country'));
        if ($request->query->get('q') && strlen($request->query->get('q')) > 1) {
            if ($data = $geoPlacesRepository->findByPostcodeOrLocality($request->getLocale(), $country, $request->query->get('q'))) {
                $out = [];
                foreach ($data as $d) {
                    $out[] = [
                        'id' => (int)$d->getId(),
                        'text' => $d->getPostcode() . ' ' . $d->getLocality()
                    ];
                }
                return new JsonResponse(['results' => $out]);
            }
        } elseif ($request->query->get('id') && $request->query->get('id') > 0) {
            if ($data = $geoPlacesRepository->findByIdAndLocale($request->query->get('id'), $request->getLocale())) {
                return new JsonResponse([
                    'id' => $data[0]->getId(),
                    'text' => $data[0]->getPostcode() . ' ' . $data[0]->getLocality()
                ]);
            }
        }

        return new JsonResponse(false);
    }

    #[Route(path: ['en' => '/ajax/tag/', 'nl' => '/ajax/tag/', 'fr' => '/ajax/tag/'], name: 'ajax_tag')]
    public function tag(Request $request, TagRepository $tagRepository)
    {
        $tags = [];
        if ($data = $tagRepository->findByTag($request->query->get('q'), $request->getLocale())) {
            foreach ($data as $t) {
                $tags[] = [
                    'id' => $t->getName(),
                    'text' => $t->getName()
                ];
            }
        }

        return new JsonResponse(['results' => $tags]);

    }
}
