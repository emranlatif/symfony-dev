<?php

namespace App\Controller;

use App\Repository\EventadvertRepository;
use App\Repository\CompanyRepository;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use App\Repository\GeoPlacesRepository;
use App\Repository\EventadvertPremiumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\HelperService;

class IndexController extends AbstractController
{

    #[Route(path: ['en' => '/login/', 'nl' => '/login/', 'fr' => '/login/'], name: 'home_login')]
    public function login(Request $request, HelperService $helper, EventadvertRepository $eventadvertRepository, ChannelRepository $channelRepository, CompanyRepository $companyRepository, CategoryRepository $categoryRepository, GeoPlacesRepository $geoPlacesRepository, EventadvertPremiumRepository $eventadvertPremiumRepository)
    {
        return $this->index($request, $helper, $eventadvertRepository, $channelRepository, $companyRepository, $categoryRepository, $geoPlacesRepository, $eventadvertPremiumRepository);
    }

    #[Route(path: ['en' => '/register/', 'nl' => '/register/', 'fr' => '/register/'], name: 'home_register')]
    public function register(Request $request, HelperService $helper, EventadvertRepository $eventadvertRepository, ChannelRepository $channelRepository, CompanyRepository $companyRepository, CategoryRepository $categoryRepository, GeoPlacesRepository $geoPlacesRepository, EventadvertPremiumRepository $eventadvertPremiumRepository)
    {
        return $this->index($request, $helper, $eventadvertRepository, $channelRepository, $companyRepository, $categoryRepository, $geoPlacesRepository, $eventadvertPremiumRepository);
    }

    #[Route([
        "en" => "/",
        "nl" => "/",
        "fr" => "/",
    ], name:  "home")]
    public function index(Request $request, HelperService $helper, EventadvertRepository $eventadvertRepository, ChannelRepository $channelRepository, CompanyRepository $companyRepository, CategoryRepository $categoryRepository, GeoPlacesRepository $geoPlacesRepository, EventadvertPremiumRepository $eventadvertPremiumRepository)
    {
        $radius_query = null;
        $radius_sel = 10;
        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];
        $location = null;
        $postcode = null;
        $start_date = $request->request->get('start_date');
        $end_date = $request->request->get('end_date');
        $hasSearchQuery = false;
        $deletedUsers = $helper->getListDeletedUser();
        $bigPremiumAdverts = $eventadvertPremiumRepository->getPaidEvents();
        $bigPremiumAdverts = array_filter($bigPremiumAdverts, function ($item) {
            if ($item->getPlan() === null || $item->getPaidDate() === null) {
                return false;
            }
            switch ($item->getPlan()) {
                case 'ONE_WEEK':
                    $interval = new \DateInterval('P7D');
                    break;
                case 'TWO_WEEKS':
                    $interval = new \DateInterval('P14D');
                    break;
                case 'ONE_MONTH':
                    $interval = new \DateInterval('P1M');
                    break;
                default:
                    $interval = new \DateInterval('P0D');
                    break;
            }
            $adjustedPaidDate = $item->getPaidDate()->add($interval);
            $item->setPaidDate($adjustedPaidDate);
            return $adjustedPaidDate >= new \DateTime;
        });

        // $date_event_start_from = null;
        // if ($request->request->has('date_event_start_from') && $request->request->get('date_event_start_from') != 'all') {
        //     $date_event_start_from = $request->request->get('date_event_start_from');
        // }

        // $date_event_start_to = null;
        // if ($request->request->has('date_event_start_to') && $request->request->get('date_event_start_to') != 'all') {
        //     $date_event_start_to = $request->request->get('date_event_start_to');
        // }

        // $date_event_end_from = null;
        // if ($request->request->has('date_event_end_from') && $request->request->get('date_event_end_from') != 'all') {
        //     $date_event_end_from = $request->request->get('date_event_end_from');
        // }

        // $date_event_end_to = null;
        // if ($request->request->has('date_event_end_to') && $request->request->get('date_event_end_to') != 'all') {
        //     $date_event_end_to = $request->request->get('date_event_end_to');
        // }

        $creationsDate = $eventadvertRepository->getEventsCreationsDate('start');
        $dates_start = [];

        foreach ($creationsDate as $creationDate) {
            $dates_start[$creationDate['date_start']->format('Y-m-d')] = $creationDate['date_start']->format('m/d/Y');
        }

        $creationsEndDate = $eventadvertRepository->getEventsCreationsDate('end');
        $dates_end = [];

        foreach ($creationsEndDate as $creationDate) {
            $dates_end[$creationDate['date_end']->format('Y-m-d')] = $creationDate['date_end']->format('m/d/Y');
        }

        // $events = $eventadvertRepository->getFutureEvents();
        $newEvents = $eventadvertRepository->findActiveUsersPremiumAdverts($deletedUsers);
        // $newEvents = $eventadvertRepository->findBy(array('paymentStatus' => 'paid'), array('id' => 'DESC'));
        // $newCompanies = $companyRepository->findBy(array('paymentStatus' => 'paid'), array('id' => 'DESC'), 10);

        $sliderItems = [];
        foreach ($newEvents as $e) {
            $tmp = [
                'type' => 'event',
                'date' => $e->getCreationDate(),
                'data' => $e,

            ];
            $sliderItems[] = $tmp;
        }

        // foreach ($newCompanies as $c) {
        //     $tmp = [
        //         'type' => 'company',
        //         'date' => $c->getCreationDate(),
        //         'data' => $c,

        //     ];
        //     $sliderItems[] = $tmp;
        // }

        usort($sliderItems, array(
            $this,
            'sortItems'
        ));

        $events = [];
        $geoPlaces = [];

        if ($request->request->has('radius') && $request->request->get('radius') > 0) {
            $radius_query = $radius_sel = $request->request->get('radius');
        }

        if ($request->request->has('radius') || $request->request->get('postcode')) {
            $hasSearchQuery = true;
        }

        if ($request->request->has('postcode') && $request->request->get('postcode') != '') {
            $postcode = $request->request->get('postcode');
            $userLocation = $helper->getUserLocation($postcode);
            if (!empty($userLocation)) {
                $location = $userLocation;
            } else {
                return $this->render('index/index.html.twig', [
                    'events' => $events,
                    'sliderItems' => $sliderItems,
                    'geoPlaces' => $geoPlaces,
                    'channels' => $channelRepository->findAll(),
                    'categories' => $categoryRepository->getFeatured($request->getLocale()),
                    'datesCreationStart' => $dates_start,
                    'datesCreationEnd' => $dates_end,
                    'radius_options_in_KM' => $radius_options_in_KM,
                    'radius_sel' => $radius_query,
                    'postcode' => $postcode,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'hasSearchQuery' => $hasSearchQuery,
                    'bigPremiumAdverts' => $bigPremiumAdverts
                ]);
            }
        }

        $latestEvents = $eventadvertRepository->getLatestCreatedEvents($deletedUsers, $start_date, null, $end_date, null, $location, $radius_query);
        if (count($latestEvents) > 0) {
            foreach ($latestEvents as $latestEvent) {
                $events[] = $latestEvent['events'];
            }
            if ($events !== []) {
                $geoPlaces = $geoPlacesRepository->getByEvents($events, $request->getLocale());
            }
        }

        # Get featured categories
        $categories = $categoryRepository->getFeatured($request->getLocale());

        $allChannels = $channelRepository->findAll();

        $channels = [];

        foreach ($allChannels as $channel) {
            $channels[] = $channel;
        }

        foreach ($events as $event) {
            $event->category_entity = (object) array();
            $event->channel_entity = (object) array();
            foreach ($categories as $category) {
                if ($event->getCategory() == $category->getId()) {
                    $event->category_entity = $category;
                    break;
                }
            }
            foreach ($allChannels as $channel) {
                if ($event->getChannel() == $channel->getId()) {
                    $event->channel_entity = $channel;
                    break;
                }
            }
        }


        foreach ($channels as $channel) {
            $eventIds = [];
            $channel->events = [];
            foreach ($events as $event) {
                if ($event->getChannel() == $channel->getId()) {
                    $channel->events[] = $event;
                    $eventIds[] = $event->getId();
                }
            }
            $channel->sliderItems = [];
            foreach ($sliderItems as $sliderItem) {
                if (in_array($sliderItem['data']->getId(), $eventIds)) {
                    $channel->sliderItems[] = $sliderItem;
                }
            }
        }

        /**
         * Limit events to 12 per channel
         */
        array_map(function ($channel) {
            $channel->events = array_slice($channel->events, 0, 12);
            return $channel;
        }, $channels);

        return $this->render('index/index.html.twig', [
            'events' => $events,
            'sliderItems' => $sliderItems,
            'geoPlaces' => $geoPlaces,
            'channels' => $channelRepository->findAll(),
            'categories' => $categoryRepository->getFeatured($request->getLocale()),
            'datesCreationStart' => $dates_start,
            'datesCreationEnd' => $dates_end,
            'radius_options_in_KM' => $radius_options_in_KM,
            'radius_sel' => $radius_query,
            'postcode' => $postcode,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'hasSearchQuery' => $hasSearchQuery,
            'bigPremiumAdverts' => $bigPremiumAdverts
        ]);
    }

    private function sortItems($element1, $element2)
    {
        $datetime1 = $element1['date']->getTimestamp();
        $datetime2 = $element2['date']->getTimestamp();
        return $datetime1 - $datetime2;
    }
}
