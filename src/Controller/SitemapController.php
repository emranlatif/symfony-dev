<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Company;
use App\Entity\GeoRegions;
use App\Entity\GeoPlaces;
use App\Repository\CategoryRepository;
use App\Repository\EventadvertRepository;
use App\Service\HelperService;

class SitemapController extends AbstractController
{
    #[Route(path: '/sitemap.xml', name: 'sitemap', defaults: ['_format' => 'xml'])]
    public function index(
        Request $request,
        CategoryRepository  $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        HelperService $helper, EntityManagerInterface $entityManager
    )
    {
        $hostName = $request->getSchemeAndHttpHost();
        $urls = [];

        // Statics Urls
        $urls[] = [
            'loc' => $this->generateUrl('home'),
            'priority' => 1.0
        ];
        $urls[] = [
            'loc' => $this->generateUrl('home_register'),
            'priority' => 1.0
        ];
        $urls[] = [
            'loc' => $this->generateUrl('cookies'),
            'priority' => 0.1
        ];
        $urls[] = [
            'loc' => $this->generateUrl('terms'),
            'priority' => 0.1
        ];
        $urls[] = [
            'loc' => $this->generateUrl('privacy'),
            'priority' => 0.1
        ];
        $urls[] = [
            'loc' => $this->generateUrl('what_about'),
            'priority' => 1.0
        ];
        $urls[] = [
            'loc' => $this->generateUrl('faq'),
            'priority' => 1.0
        ];
        $urls[] = [
            'loc' => $this->generateUrl('advertise'),
            'priority' => 1.0
        ];
        $urls[] = [
            'loc' => $this->generateUrl('deelnemer'),
            'priority' => 1.0
        ];
        $urls[] = [
            'loc' => $this->generateUrl('sales'),
            'priority' => 0.8
        ];
        $urls[] = [
            'loc' => $this->generateUrl('events'),
            'priority' => 0.8
        ];
        $urls[] = [
            'loc' => $this->generateUrl('local_stores'),
            'priority' => 0.8
        ];
        $urls[] = [
            'loc' => $this->generateUrl('company_index'),
            'priority' => 0.8
        ];

        $channelsID = [
            1 => $this->generateUrl('sales'),
            2 => $this->generateUrl('events'),
            3 => $this->generateUrl('local_stores')
        ];
        $deletedUsers = $helper->getListDeletedUser();

        // Eventadverts
        foreach ( $channelsID as $channelID => $url )
        {
            foreach ($categoryRepository->findBy(['channel' => $channelID]) as $c) {
                $categoryUrl = $url.$c->getTitleSlug().'/';
                $urls[] = [
                    'loc' => $categoryUrl,
                    'priority' => 0.8
                ];

                foreach ($categoryRepository->getByTitleSlug($request->getLocale(), $c->getTitleSlug()) as $cat) {
                    $categories = [];
                    $categories[] = $cat->getId();

                    $events = $eventadvertRepository->getLatestCreatedEvents($deletedUsers, null, null, null, null, null, null, $categories);

                    if (count($events) > 0) {
                        foreach ($events as $event) {
                            $urls[] = [
                                'loc' => $categoryUrl.$event['events']->getTitleSlug().'.html',
                                'priority' => 0.8
                            ];
                        }
                    }
                }
            }
        }


        // Company
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $provinces = $entityManager->getRepository(GeoRegions::class)->findBy([
            'iso' => $country,
            'level' => 2,
            'language' => $locale
        ]);

        foreach ( $provinces as $province )
        {
            $provinceUrl = $this->generateUrl('company_index').$province->getNameDirify().'/';
            $urls[] = [
                'loc' => $provinceUrl,
                'priority' => 0.8
            ];

            $cities = $entityManager->getRepository(GeoPlaces::class)->findActiveCities($locale, $country, $province->getIso2());

            if ( $cities )
            {
                foreach ($cities as $city ) {
                    $cityUrl = $provinceUrl.$city->getLocalityDirify().'/';
                    $urls[] = [
                        'loc' => $cityUrl,
                        'priority' => 0.64
                    ];

                    $companies = $entityManager->getRepository(Company::class)->findBy([
                        'geoPlacesId' => $city->getId()
                    ]);

                    if ( $companies )
                    {
                        foreach ( $companies as $company )
                        {
                            $urls[] = [
                                'loc' => $cityUrl.$company->getCompanynameSlug().'.html',
                                'priority' => 0.64
                            ];
                        }

                    }
                }
            }
        }

        $response = new Response(
            $this->renderView('sitemap/index.html.twig', [
                'urls' => $urls,
                'hostName' => $hostName
            ]),
            200
        );

        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    #[Route(path: '/sitemap-algemeen.xml', name: 'sitemap-general', defaults: ['_format' => 'xml'])]
    public function general()
    {
        return $this->render('sitemap/general.html.twig', [
            'controller_name' => 'ContactController',
        ]);
    }

    #[Route(path: '/sitemap-bedrijven.xml', name: 'sitemap-companies', defaults: ['_format' => 'xml'])]
    public function companies(EntityManagerInterface $entityManager)
    {

        $companies = $entityManager->getRepository(Company::class)->findAll();


        return $this->render('sitemap/companies.html.twig', [
            'companies' => $companies,
        ]);
    }

    #[Route(path: '/sitemap-evenementen.xml', name: 'sitemap-events', defaults: ['_format' => 'xml'])]
    public function events()
    {
        return $this->render('sitemap/events.html.twig', [
            'controller_name' => 'ContactController',
        ]);
    }
}
