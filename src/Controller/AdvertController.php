<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\EventadvertPremium;
use App\Entity\User;
use App\Entity\ViewPremiumAdvert;
use App\Entity\ViewBigPremiumAdvert;
use App\Service\HelperService;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use App\Repository\EventadvertRepository;
use App\Repository\GeoPlacesRepository;
use App\Repository\GeoRegionsRepository;
use App\Form\CompanyFormType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class AdvertController extends AbstractController
{
    #[Route(path: ['en' => '/sales/', 'nl' => '/promoties/', 'fr' => '/promotions/'], name: 'sales')]
    public function sales(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        PaginatorInterface    $paginator,
        HelperService $helper
    )
    {
        $deletedUsers = $helper->getListDeletedUser();
        $categories = [];
        foreach ($categoryRepository->findBy(['channel' => 1]) as $c) {
            $categories[] = $c->getId();
        }

        $radius_query = null;
        $radius_sel = 10;
        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];
        $location = null;
        $postcode = null;
        $hasSearchQuery = false;

        $date_event_start_from = null;
        if ( $request->request->has('date_event_start_from') && $request->request->get('date_event_start_from') != 'all') {
            $date_event_start_from = $request->request->get('date_event_start_from');
        }

        $date_event_start_to = null;
        if ( $request->request->has('date_event_start_to') && $request->request->get('date_event_start_to') != 'all') {
            $date_event_start_to = $request->request->get('date_event_start_to');
        }

        $date_event_end_from = null;
        if ( $request->request->has('date_event_end_from') && $request->request->get('date_event_end_from') != 'all' ) {
            $date_event_end_from = $request->request->get('date_event_end_from');
        }

        $date_event_end_to = null;
        if ( $request->request->has('date_event_end_to') && $request->request->get('date_event_end_to') != 'all' ) {
            $date_event_end_to = $request->request->get('date_event_end_to');
        }

        $creationsDate = $eventadvertRepository->getEventsCreationsDate('start');
        $dates_start = [];

        foreach ( $creationsDate as $creationDate )
        {
            $dates_start[$creationDate['date_start']->format('Y-m-d')] = $creationDate['date_start']->format('m/d/Y');
        }

        $creationsEndDate = $eventadvertRepository->getEventsCreationsDate('end');
        $dates_end = [];

        foreach ( $creationsEndDate as $creationDate )
        {
            $dates_end[$creationDate['date_end']->format('Y-m-d')] = $creationDate['date_end']->format('m/d/Y');
        }

        if ( $request->request->has('radius') && $request->request->get('radius') > 0 ) {
            $radius_query = $radius_sel = $request->request->get('radius');
        }

        $data['channel'] = 'Promoties';
        $sliderItems = [];
        $newEvents = $eventadvertRepository->getEventsPaidInCategories($deletedUsers, $categories);
        if ($newEvents !== null) {
            foreach ($newEvents as $e) {
                $tmp = [
                    'type' => 'event',
                    'date' => $e->getCreationDate(),
                    'data' => $e,

                ];
                $sliderItems[] = $tmp;
            }

            usort($sliderItems, array(
                $this,
                'sortItems'
            ));
        }

        if ($request->request->has('radius') || $request->request->get('postcode')) {
            $hasSearchQuery = true;
        }

        if ( $request->request->has('postcode') && $request->request->get('postcode') != '' ) {
            $postcode = $request->request->get('postcode');
            $userLocation = $helper->getUserLocation($postcode);
            if (!empty($userLocation)) {
                $location = $userLocation;
            } else {
                $events = [];
                $geoPlaces = [];
                return $this->render('index/index.html.twig', [
                    'events' => $events,
                    'sliderItems' => $sliderItems,
                    'geoPlaces' => $geoPlaces,
                    'channels' => $channelRepository->findAll(),
                    'singleChannel' => $channelRepository->findBy(['id' => 1]),
                    'channelShowed' => 1,
                    'categories' => $categoryRepository->getFeatured($request->getLocale()),
                    'datesCreationStart' => $dates_start,
                    'datesCreationEnd' => $dates_end,
                    'date_event_start_from' => $date_event_start_from,
                    'date_event_start_to' => $date_event_start_to,
                    'date_event_end_from' => $date_event_end_from,
                    'date_event_end_to' => $date_event_end_to,
                    'radius_options_in_KM' => $radius_options_in_KM,
                    'radius_sel' => $radius_query,
                    'postcode' => $postcode,
                    'hasSearchQuery' => $hasSearchQuery
                ]);
            }
        }

        $data = $this->getData($deletedUsers, $request->getLocale(), $categories, $channelRepository, $categoryRepository, $eventadvertRepository, $geoPlacesRepository, $paginator, $request, $date_event_start_from, $date_event_start_to, $date_event_end_from, $date_event_end_to, $location, $radius_query);


        $data['eventsPaid'] = $sliderItems;
        $data['channels'] = $channelRepository->findAll();
        $data['singleChannel'] = $channelRepository->findBy(['id' => 1]);
        $data['channelShowed'] = 1;
        $data['categories'] = $categoryRepository->getFeatured($request->getLocale());
        $data['datesCreationStart'] = $dates_start;
        $data['datesCreationEnd'] = $dates_end;
        $data['date_event_start_from'] = $date_event_start_from;
        $data['date_event_start_to'] = $date_event_start_to;
        $data['date_event_end_from'] = $date_event_end_from;
        $data['date_event_end_to'] = $date_event_end_to;
        $data['radius_options_in_KM'] = $radius_options_in_KM;
        $data['radius_sel'] = $radius_query;
        $data['postcode'] = $postcode;
        $data['hasSearchQuery'] = $hasSearchQuery;

        return $this->render('advert/index.html.twig', $data);
    }

    #[Route(path: ['en' => '/events/', 'nl' => '/evenementen/', 'fr' => '/evenements/'], name: 'events')]
    public function events(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        PaginatorInterface    $paginator,
        HelperService $helper
    )
    {
        $deletedUsers = $helper->getListDeletedUser();
        $categories = [];
        foreach ($categoryRepository->findBy(['channel' => 2]) as $c) {
            $categories[] = $c->getId();
        }

        $radius_query = null;
        $radius_sel = 10;
        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];
        $location = null;
        $postcode = null;
        $hasSearchQuery = false;

        $date_event_start_from = null;
        if ( $request->request->has('date_event_start_from') && $request->request->get('date_event_start_from') != 'all') {
            $date_event_start_from = $request->request->get('date_event_start_from');
        }

        $date_event_start_to = null;
        if ( $request->request->has('date_event_start_to') && $request->request->get('date_event_start_to') != 'all') {
            $date_event_start_to = $request->request->get('date_event_start_to');
        }

        $date_event_end_from = null;
        if ( $request->request->has('date_event_end_from') && $request->request->get('date_event_end_from') != 'all' ) {
            $date_event_end_from = $request->request->get('date_event_end_from');
        }

        $date_event_end_to = null;
        if ( $request->request->has('date_event_end_to') && $request->request->get('date_event_end_to') != 'all' ) {
            $date_event_end_to = $request->request->get('date_event_end_to');
        }

        $eventBetweenDate = null;
        if ( $request->request->has('eventBetweenDate') && $request->request->get('eventBetweenDate') != 'all' ) {
            $eventBetweenDate = $request->request->get('eventBetweenDate');
        }

        $creationsDate = $eventadvertRepository->getEventsCreationsDate('start');
        $dates_start = [];

        foreach ( $creationsDate as $creationDate )
        {
            $dates_start[$creationDate['date_start']->format('Y-m-d')] = $creationDate['date_start']->format('m/d/Y');
        }

        $creationsEndDate = $eventadvertRepository->getEventsCreationsDate('end');
        $dates_end = [];

        foreach ( $creationsEndDate as $creationDate )
        {
            $dates_end[$creationDate['date_end']->format('Y-m-d')] = $creationDate['date_end']->format('m/d/Y');
        }

        if ( $request->request->has('radius') && $request->request->get('radius') > 0 ) {
            $radius_query = $radius_sel = $request->request->get('radius');
        }

        $data['channel'] = 'Evenementen';

        $sliderItems = [];
        $newEvents = $eventadvertRepository->getEventsPaidInCategories($deletedUsers, $categories);
        if ($newEvents !== null) {
            foreach ($newEvents as $e) {
                $tmp = [
                    'type' => 'event',
                    'date' => $e->getCreationDate(),
                    'data' => $e,

                ];
                $sliderItems[] = $tmp;
            }

            usort($sliderItems, array(
                $this,
                'sortItems'
            ));
        }

        if ($request->request->has('radius') || $request->request->get('postcode')) {
            $hasSearchQuery = true;
        }

        if ( $request->request->has('postcode') && $request->request->get('postcode') != '' ) {
            $postcode = $request->request->get('postcode');
            $userLocation = $helper->getUserLocation($postcode);
            if (!empty($userLocation)) {
                $location = $userLocation;
            } else {
                $events = [];
                $geoPlaces = [];
                return $this->render('index/index.html.twig', [
                    'events' => $events,
                    'sliderItems' => $sliderItems,
                    'geoPlaces' => $geoPlaces,
                    'channels' => $channelRepository->findAll(),
                    'singleChannel' => $channelRepository->findBy(['id' => 2]),
                    'channelShowed' => 2,
                    'categories' => $categoryRepository->getFeatured($request->getLocale()),
                    'datesCreationStart' => $dates_start,
                    'datesCreationEnd' => $dates_end,
                    'date_event_start_from' => $date_event_start_from,
                    'date_event_start_to' => $date_event_start_to,
                    'date_event_end_from' => $date_event_end_from,
                    'date_event_end_to' => $date_event_end_to,
                    'eventBetweenDate' => $eventBetweenDate,
                    'radius_options_in_KM' => $radius_options_in_KM,
                    'radius_sel' => $radius_query,
                    'postcode' => $postcode,
                    'hasSearchQuery' => $hasSearchQuery
                ]);
            }
        }

        $data = $this->getData($deletedUsers, $request->getLocale(), $categories, $channelRepository, $categoryRepository, $eventadvertRepository, $geoPlacesRepository, $paginator, $request, $date_event_start_from, $date_event_start_to, $date_event_end_from, $date_event_end_to, $location, $radius_query);


        $data['eventsPaid'] = $sliderItems;
        $data['channels'] = $channelRepository->findAll();
        $data['singleChannel'] = $channelRepository->findBy(['id' => 2]);
        $data['channelShowed'] = 2;
        $data['categories'] = $categoryRepository->getFeatured($request->getLocale());
        $data['datesCreationStart'] = $dates_start;
        $data['datesCreationEnd'] = $dates_end;
        $data['date_event_start_from'] = $date_event_start_from;
        $data['date_event_start_to'] = $date_event_start_to;
        $data['date_event_end_from'] = $date_event_end_from;
        $data['date_event_end_to'] = $date_event_end_to;
        $data['radius_options_in_KM'] = $radius_options_in_KM;
        $data['radius_sel'] = $radius_query;
        $data['postcode'] = $postcode;
        $data['hasSearchQuery'] = $hasSearchQuery;

        return $this->render('advert/index.html.twig', $data);
    }

    #[Route(path: ['en' => '/local-stores/', 'nl' => '/handelaars/', 'fr' => '/marchands/'], name: 'local_stores')]
    public function stores(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        PaginatorInterface    $paginator,
        HelperService $helper
    )
    {
        $deletedUsers = $helper->getListDeletedUser();
        $categories = [];
        foreach ($categoryRepository->findBy(['channel' => 3]) as $c) {
            $categories[] = $c->getId();
        }

        $radius_query = null;
        $radius_sel = 10;
        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];
        $location = null;
        $postcode = null;
        $hasSearchQuery = false;

        $date_event_start_from = null;
        if ( $request->request->has('date_event_start_from') && $request->request->get('date_event_start_from') != 'all') {
            $date_event_start_from = $request->request->get('date_event_start_from');
        }

        $date_event_start_to = null;
        if ( $request->request->has('date_event_start_to') && $request->request->get('date_event_start_to') != 'all') {
            $date_event_start_to = $request->request->get('date_event_start_to');
        }

        $date_event_end_from = null;
        if ( $request->request->has('date_event_end_from') && $request->request->get('date_event_end_from') != 'all' ) {
            $date_event_end_from = $request->request->get('date_event_end_from');
        }

        $date_event_end_to = null;
        if ( $request->request->has('date_event_end_to') && $request->request->get('date_event_end_to') != 'all' ) {
            $date_event_end_to = $request->request->get('date_event_end_to');
        }

        $creationsDate = $eventadvertRepository->getEventsCreationsDate('start');
        $dates_start = [];

        foreach ( $creationsDate as $creationDate )
        {
            $dates_start[$creationDate['date_start']->format('Y-m-d')] = $creationDate['date_start']->format('m/d/Y');
        }

        $creationsEndDate = $eventadvertRepository->getEventsCreationsDate('end');
        $dates_end = [];

        foreach ( $creationsEndDate as $creationDate )
        {
            $dates_end[$creationDate['date_end']->format('Y-m-d')] = $creationDate['date_end']->format('m/d/Y');
        }

        if ( $request->request->has('radius') && $request->request->get('radius') > 0 ) {
            $radius_query = $radius_sel = $request->request->get('radius');
        }

        $data['channel'] = 'Handelaars';

        $sliderItems = [];
        $newEvents = $eventadvertRepository->getEventsPaidInCategories($deletedUsers, $categories);
        if ($newEvents !== null) {
            foreach ($newEvents as $e) {
                $tmp = [
                    'type' => 'event',
                    'date' => $e->getCreationDate(),
                    'data' => $e,

                ];
                $sliderItems[] = $tmp;
            }

            usort($sliderItems, array(
                $this,
                'sortItems'
            ));
        }

        if ($request->request->has('radius') || $request->request->get('postcode')) {
            $hasSearchQuery = true;
        }

        if ( $request->request->has('postcode') && $request->request->get('postcode') != '' ) {
            $postcode = $request->request->get('postcode');
            $userLocation = $helper->getUserLocation($postcode);
            if (!empty($userLocation)) {
                $location = $userLocation;
            } else {
                $events = [];
                $geoPlaces = [];
                return $this->render('index/index.html.twig', [
                    'events' => $events,
                    'sliderItems' => $sliderItems,
                    'geoPlaces' => $geoPlaces,
                    'channels' => $channelRepository->findAll(),
                    'singleChannel' => $channelRepository->findBy(['id' => 3]),
                    'channelShowed' => 3,
                    'categories' => $categoryRepository->getFeatured($request->getLocale()),
                    'datesCreationStart' => $dates_start,
                    'datesCreationEnd' => $dates_end,
                    'date_event_start_from' => $date_event_start_from,
                    'date_event_start_to' => $date_event_start_to,
                    'date_event_end_from' => $date_event_end_from,
                    'date_event_end_to' => $date_event_end_to,
                    'radius_options_in_KM' => $radius_options_in_KM,
                    'radius_sel' => $radius_query,
                    'postcode' => $postcode,
                    'hasSearchQuery' => $hasSearchQuery
                ]);
            }
        }

        $data = $this->getData($deletedUsers, $request->getLocale(), $categories, $channelRepository, $categoryRepository, $eventadvertRepository, $geoPlacesRepository, $paginator, $request, $date_event_start_from, $date_event_start_to, $date_event_end_from, $date_event_end_to, $location, $radius_query);

        $data['eventsPaid'] = $sliderItems;
        $data['channels'] = $channelRepository->findAll();
        $data['singleChannel'] = $channelRepository->findBy(['id' => 3]);
        $data['channelShowed'] = 3;
        $data['categories'] = $categoryRepository->getFeatured($request->getLocale());
        $data['datesCreationStart'] = $dates_start;
        $data['datesCreationEnd'] = $dates_end;
        $data['date_event_start_from'] = $date_event_start_from;
        $data['date_event_start_to'] = $date_event_start_to;
        $data['date_event_end_from'] = $date_event_end_from;
        $data['date_event_end_to'] = $date_event_end_to;
        $data['radius_options_in_KM'] = $radius_options_in_KM;
        $data['radius_sel'] = $radius_query;
        $data['postcode'] = $postcode;
        $data['hasSearchQuery'] = $hasSearchQuery;

        return $this->render('advert/index.html.twig', $data);
    }

    /**
        /**
    */
    #[Route(path: ['en' => '/sales/{category}/', 'nl' => '/promoties/{category}/', 'fr' => '/promotions/{category}/'], name: 'sales_category', defaults: ['category' => 'default'])]
    public function salesCategory(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        string                $category,
        PaginatorInterface    $paginator,
        HelperService $helper
    )
    {
        $deletedUsers = $helper->getListDeletedUser();
        $categoryData = null;
        $categories = [];
        foreach ($categoryRepository->getByTitleSlug($request->getLocale(), $category) as $c) {
            $categories[] = $c->getId();
            $categoryData = $c;
        }

        $radius_query = null;
        $radius_sel = 10;
        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];
        $location = null;
        $postcode = null;
        $hasSearchQuery = false;

        $date_event_start_from = null;
        if ( $request->request->has('date_event_start_from') && $request->request->get('date_event_start_from') != 'all') {
            $date_event_start_from = $request->request->get('date_event_start_from');
        }

        $date_event_start_to = null;
        if ( $request->request->has('date_event_start_to') && $request->request->get('date_event_start_to') != 'all') {
            $date_event_start_to = $request->request->get('date_event_start_to');
        }

        $date_event_end_from = null;
        if ( $request->request->has('date_event_end_from') && $request->request->get('date_event_end_from') != 'all' ) {
            $date_event_end_from = $request->request->get('date_event_end_from');
        }

        $date_event_end_to = null;
        if ( $request->request->has('date_event_end_to') && $request->request->get('date_event_end_to') != 'all' ) {
            $date_event_end_to = $request->request->get('date_event_end_to');
        }

        $creationsDate = $eventadvertRepository->getEventsCreationsDate('start');
        $dates_start = [];

        foreach ( $creationsDate as $creationDate )
        {
            $dates_start[$creationDate['date_start']->format('Y-m-d')] = $creationDate['date_start']->format('m/d/Y');
        }

        $creationsEndDate = $eventadvertRepository->getEventsCreationsDate('end');
        $dates_end = [];

        foreach ( $creationsEndDate as $creationDate )
        {
            $dates_end[$creationDate['date_end']->format('Y-m-d')] = $creationDate['date_end']->format('m/d/Y');
        }

        if ( $request->request->has('radius') && $request->request->get('radius') > 0 ) {
            $radius_query = $radius_sel = $request->request->get('radius');
        }

        $data['category'] = $categoryData;


        $sliderItems = [];
        $newEvents = $eventadvertRepository->getEventsPaidInCategories($deletedUsers, $categories);
        if ($newEvents !== null) {
            foreach ($newEvents as $e) {
                $tmp = [
                    'type' => 'event',
                    'date' => $e->getCreationDate(),
                    'data' => $e,

                ];
                $sliderItems[] = $tmp;
            }

            usort($sliderItems, array(
                $this,
                'sortItems'
            ));
        }

        if ( count($sliderItems) == 0 || count($sliderItems) < 5 )
        {
            $sliderItems = $this->getMainCategoriePaidsAdverts($deletedUsers, $categoryRepository, $eventadvertRepository, 'promoties');
        }

        if ($request->request->has('radius') || $request->request->get('postcode')) {
            $hasSearchQuery = true;
        }

        if ( $request->request->has('postcode') && $request->request->get('postcode') != '' ) {
            $postcode = $request->request->get('postcode');
            $userLocation = $helper->getUserLocation($postcode);
            if (!empty($userLocation)) {
                $location = $userLocation;
            } else {
                $events = [];
                $geoPlaces = [];
                return $this->render('index/index.html.twig', [
                    'events' => $events,
                    'sliderItems' => $sliderItems,
                    'geoPlaces' => $geoPlaces,
                    'channels' => $channelRepository->findAll(),
                    'singleChannel' => $channelRepository->findBy(['id' => 1]),
                    'subChannel' => $categoryData,
                    'channelShowed' => 1,
                    'categories' => $categoryRepository->getFeatured($request->getLocale()),
                    'datesCreationStart' => $dates_start,
                    'datesCreationEnd' => $dates_end,
                    'date_event_start_from' => $date_event_start_from,
                    'date_event_start_to' => $date_event_start_to,
                    'date_event_end_from' => $date_event_end_from,
                    'date_event_end_to' => $date_event_end_to,
                    'radius_options_in_KM' => $radius_options_in_KM,
                    'radius_sel' => $radius_query,
                    'postcode' => $postcode,
                    'hasSearchQuery' => $hasSearchQuery
                ]);
            }
        }

        $data = $this->getData($deletedUsers, $request->getLocale(), $categories, $channelRepository, $categoryRepository, $eventadvertRepository, $geoPlacesRepository, $paginator, $request, $date_event_start_from, $date_event_start_to, $date_event_end_from, $date_event_end_to, $location, $radius_query);


        $data['eventsPaid'] = $sliderItems;
        $data['channels'] = $channelRepository->findAll();
        $data['singleChannel'] = $channelRepository->findBy(['id' => 1]);
        $data['subChannel'] = $categoryData;
        $data['channelShowed'] = 1;
        $data['categories'] = $categoryRepository->getFeatured($request->getLocale());
        $data['datesCreationStart'] = $dates_start;
        $data['datesCreationEnd'] = $dates_end;
        $data['date_event_start_from'] = $date_event_start_from;
        $data['date_event_start_to'] = $date_event_start_to;
        $data['date_event_end_from'] = $date_event_end_from;
        $data['date_event_end_to'] = $date_event_end_to;
        $data['radius_options_in_KM'] = $radius_options_in_KM;
        $data['radius_sel'] = $radius_query;
        $data['postcode'] = $postcode;
        $data['hasSearchQuery'] = $hasSearchQuery;

        return $this->render('advert/category.html.twig', $data);
    }

    #[Route(path: ['en' => '/events/{category}/', 'nl' => '/evenementen/{category}/', 'fr' => '/evenements/{category}/'], name: 'events_category')]
    public function eventsCategory(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        string                $category,
        PaginatorInterface    $paginator,
        HelperService $helper
    )
    {
        $deletedUsers = $helper->getListDeletedUser();
        $categoryData = null;
        $categories = [];
        foreach ($categoryRepository->getByTitleSlug($request->getLocale(), $category) as $c) {
            $categories[] = $c->getId();
            $categoryData = $c;
        }

        $radius_query = null;
        $radius_sel = 10;
        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];
        $location = null;
        $postcode = null;
        $hasSearchQuery = false;

        $date_event_start_from = null;
        if ( $request->request->has('date_event_start_from') && $request->request->get('date_event_start_from') != 'all') {
            $date_event_start_from = $request->request->get('date_event_start_from');
        }

        $date_event_start_to = null;
        if ( $request->request->has('date_event_start_to') && $request->request->get('date_event_start_to') != 'all') {
            $date_event_start_to = $request->request->get('date_event_start_to');
        }

        $date_event_end_from = null;
        if ( $request->request->has('date_event_end_from') && $request->request->get('date_event_end_from') != 'all' ) {
            $date_event_end_from = $request->request->get('date_event_end_from');
        }

        $date_event_end_to = null;
        if ( $request->request->has('date_event_end_to') && $request->request->get('date_event_end_to') != 'all' ) {
            $date_event_end_to = $request->request->get('date_event_end_to');
        }

        $creationsDate = $eventadvertRepository->getEventsCreationsDate('start');
        $dates_start = [];

        foreach ( $creationsDate as $creationDate )
        {
            $dates_start[$creationDate['date_start']->format('Y-m-d')] = $creationDate['date_start']->format('m/d/Y');
        }

        $creationsEndDate = $eventadvertRepository->getEventsCreationsDate('end');
        $dates_end = [];

        foreach ( $creationsEndDate as $creationDate )
        {
            $dates_end[$creationDate['date_end']->format('Y-m-d')] = $creationDate['date_end']->format('m/d/Y');
        }

        if ( $request->request->has('radius') && $request->request->get('radius') > 0 ) {
            $radius_query = $radius_sel = $request->request->get('radius');
        }

        $data['category'] = $categoryData;

        $sliderItems = [];
        $newEvents = $eventadvertRepository->getEventsPaidInCategories($deletedUsers, $categories);
        if ($newEvents !== null) {
            foreach ($newEvents as $e) {
                $tmp = [
                    'type' => 'event',
                    'date' => $e->getCreationDate(),
                    'data' => $e,

                ];
                $sliderItems[] = $tmp;
            }

            usort($sliderItems, array(
                $this,
                'sortItems'
            ));
        }

        if ( count($sliderItems) == 0 || count($sliderItems) < 5 )
        {
            $sliderItems = $this->getMainCategoriePaidsAdverts($deletedUsers, $categoryRepository, $eventadvertRepository, 'evenementen');
        }

        if ($request->request->has('radius') || $request->request->get('postcode')) {
            $hasSearchQuery = true;
        }

        if ( $request->request->has('postcode') && $request->request->get('postcode') != '' ) {
            $postcode = $request->request->get('postcode');
            $userLocation = $helper->getUserLocation($postcode);
            if (!empty($userLocation)) {
                $location = $userLocation;
            } else {
                $events = [];
                $geoPlaces = [];
                return $this->render('index/index.html.twig', [
                    'events' => $events,
                    'sliderItems' => $sliderItems,
                    'geoPlaces' => $geoPlaces,
                    'channels' => $channelRepository->findAll(),
                    'singleChannel' => $channelRepository->findBy(['id' => 2]),
                    'subChannel' => $categoryData,
                    'channelShowed' => 2,
                    'categories' => $categoryRepository->getFeatured($request->getLocale()),
                    'datesCreationStart' => $dates_start,
                    'datesCreationEnd' => $dates_end,
                    'date_event_start_from' => $date_event_start_from,
                    'date_event_start_to' => $date_event_start_to,
                    'date_event_end_from' => $date_event_end_from,
                    'date_event_end_to' => $date_event_end_to,
                    'radius_options_in_KM' => $radius_options_in_KM,
                    'radius_sel' => $radius_query,
                    'postcode' => $postcode,
                    'hasSearchQuery' => $hasSearchQuery
                ]);
            }
        }

        $data = $this->getData($deletedUsers, $request->getLocale(), $categories, $channelRepository, $categoryRepository, $eventadvertRepository, $geoPlacesRepository, $paginator, $request, $date_event_start_from, $date_event_start_to, $date_event_end_from, $date_event_end_to, $location, $radius_query);


        $data['eventsPaid'] = $sliderItems;
        $data['channels'] = $channelRepository->findAll();
        $data['singleChannel'] = $channelRepository->findBy(['id' => 2]);
        $data['subChannel'] = $categoryData;
        $data['channelShowed'] = 2;
        $data['categories'] = $categoryRepository->getFeatured($request->getLocale());
        $data['datesCreationStart'] = $dates_start;
        $data['datesCreationEnd'] = $dates_end;
        $data['date_event_start_from'] = $date_event_start_from;
        $data['date_event_start_to'] = $date_event_start_to;
        $data['date_event_end_from'] = $date_event_end_from;
        $data['date_event_end_to'] = $date_event_end_to;
        $data['radius_options_in_KM'] = $radius_options_in_KM;
        $data['radius_sel'] = $radius_query;
        $data['postcode'] = $postcode;
        $data['hasSearchQuery'] = $hasSearchQuery;


        return $this->render('advert/category.html.twig', $data);
    }

    #[Route(path: ['en' => '/local-stores/{category}/', 'nl' => '/handelaars/{category}/', 'fr' => '/marchands/{category}/'], name: 'local_stores_category')]
    public function storesCategory(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        string                $category,
        PaginatorInterface    $paginator,
        HelperService $helper
    )
    {
        $deletedUsers = $helper->getListDeletedUser();
        $categoryData = null;
        $categories = [];
        foreach ($categoryRepository->getByTitleSlug($request->getLocale(), $category) as $c) {
            $categories[] = $c->getId();
            $categoryData = $c;
        }

        $radius_query = null;
        $radius_sel = 10;
        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];
        $location = null;
        $postcode = null;
        $hasSearchQuery = false;

        $date_event_start_from = null;
        if ( $request->request->has('date_event_start_from') && $request->request->get('date_event_start_from') != 'all') {
            $date_event_start_from = $request->request->get('date_event_start_from');
        }

        $date_event_start_to = null;
        if ( $request->request->has('date_event_start_to') && $request->request->get('date_event_start_to') != 'all') {
            $date_event_start_to = $request->request->get('date_event_start_to');
        }

        $date_event_end_from = null;
        if ( $request->request->has('date_event_end_from') && $request->request->get('date_event_end_from') != 'all' ) {
            $date_event_end_from = $request->request->get('date_event_end_from');
        }

        $date_event_end_to = null;
        if ( $request->request->has('date_event_end_to') && $request->request->get('date_event_end_to') != 'all' ) {
            $date_event_end_to = $request->request->get('date_event_end_to');
        }

        $creationsDate = $eventadvertRepository->getEventsCreationsDate('start');
        $dates_start = [];

        foreach ( $creationsDate as $creationDate )
        {
            $dates_start[$creationDate['date_start']->format('Y-m-d')] = $creationDate['date_start']->format('m/d/Y');
        }

        $creationsEndDate = $eventadvertRepository->getEventsCreationsDate('end');
        $dates_end = [];

        foreach ( $creationsEndDate as $creationDate )
        {
            $dates_end[$creationDate['date_end']->format('Y-m-d')] = $creationDate['date_end']->format('m/d/Y');
        }

        if ( $request->request->has('radius') && $request->request->get('radius') > 0 ) {
            $radius_query = $radius_sel = $request->request->get('radius');
        }

        $data['category'] = $categoryData;

        $sliderItems = [];
        $newEvents = $eventadvertRepository->getEventsPaidInCategories($deletedUsers, $categories);
        if ($newEvents !== null) {
            foreach ($newEvents as $e) {
                $tmp = [
                    'type' => 'event',
                    'date' => $e->getCreationDate(),
                    'data' => $e,

                ];
                $sliderItems[] = $tmp;
            }

            usort($sliderItems, array(
                $this,
                'sortItems'
            ));
        }

        if ( count($sliderItems) == 0 || count($sliderItems) < 5 )
        {
            $sliderItems = $this->getMainCategoriePaidsAdverts($deletedUsers, $categoryRepository, $eventadvertRepository, 'handelaars');
        }

        if ($request->request->has('radius') || $request->request->get('postcode')) {
            $hasSearchQuery = true;
        }

        if ( $request->request->has('postcode') && $request->request->get('postcode') != '' ) {
            $postcode = $request->request->get('postcode');
            $userLocation = $helper->getUserLocation($postcode);
            if (!empty($userLocation)) {
                $location = $userLocation;
            } else {
                $events = [];
                $geoPlaces = [];
                return $this->render('index/index.html.twig', [
                    'events' => $events,
                    'sliderItems' => $sliderItems,
                    'geoPlaces' => $geoPlaces,
                    'channels' => $channelRepository->findAll(),
                    'singleChannel' => $channelRepository->findBy(['id' => 3]),
                    'subChannel' => $categoryData,
                    'channelShowed' => 3,
                    'categories' => $categoryRepository->getFeatured($request->getLocale()),
                    'datesCreationStart' => $dates_start,
                    'datesCreationEnd' => $dates_end,
                    'date_event_start_from' => $date_event_start_from,
                    'date_event_start_to' => $date_event_start_to,
                    'date_event_end_from' => $date_event_end_from,
                    'date_event_end_to' => $date_event_end_to,
                    'radius_options_in_KM' => $radius_options_in_KM,
                    'radius_sel' => $radius_query,
                    'postcode' => $postcode,
                    'hasSearchQuery' => $hasSearchQuery
                ]);
            }
        }

        $data = $this->getData($deletedUsers, $request->getLocale(), $categories, $channelRepository, $categoryRepository, $eventadvertRepository, $geoPlacesRepository, $paginator, $request, $date_event_start_from, $date_event_start_to, $date_event_end_from, $date_event_end_to, $location, $radius_query);


        $data['eventsPaid'] = $sliderItems;
        $data['channels'] = $channelRepository->findAll();
        $data['singleChannel'] = $channelRepository->findBy(['id' => 3]);
        $data['subChannel'] = $categoryData;
        $data['channelShowed'] = 3;
        $data['categories'] = $categoryRepository->getFeatured($request->getLocale());
        $data['datesCreationStart'] = $dates_start;
        $data['datesCreationEnd'] = $dates_end;
        $data['date_event_start_from'] = $date_event_start_from;
        $data['date_event_start_to'] = $date_event_start_to;
        $data['date_event_end_from'] = $date_event_end_from;
        $data['date_event_end_to'] = $date_event_end_to;
        $data['radius_options_in_KM'] = $radius_options_in_KM;
        $data['radius_sel'] = $radius_query;
        $data['postcode'] = $postcode;
        $data['hasSearchQuery'] = $hasSearchQuery;


        return $this->render('advert/category.html.twig', $data);
    }

    #[Route(path: ['en' => '/sales/{category}/{slug}.html', 'nl' => '/promoties/{category}/{slug}.html', 'fr' => '/promotions/{category}/{slug}.html'], name: 'sales_advert')]
    public function salesAdvert(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        string                $category,
        string                $slug,
        EntityManagerInterface $entityManager
    )
    {

        $event = $eventadvertRepository->findOneBy(['titleSlug' => $slug]);

        if ( !$event or $event?->getStatus() === 0 )
        {
            $this->addFlash('danger', 'Deze advertentie is niet meer beschikbaar');
            header('Refresh: 2; url=/');
            return $this->json(['Deze advertentie is niet meer beschikbaar']);
        }

        $now = new \Datetime();
        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $event->getUserId()]);

        if ( $user->getBlocked() == true ) {
            header('Refresh: 2; url=/');
            return $this->json(['Deze advertentie is niet meer beschikbaar']);
        }


        $geo = $event->getGeoPlacesId() > 0 ? $event->getGeoPlacesId() : $event->getCompany()->getGeoPlacesId();
        // $geo = $event->getCompany()->getGeoPlacesId();

        $channel = $channelRepository->find($event->getChannel());
        $category = $categoryRepository->find($event->getCategory());

        $eventadvertRepository->processView($event->getId());
        $geoPlace = $geoPlacesRepository->findByIdAndLocale($geo, $request->getLocale());

        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $geoCity = $geoPlacesRepository->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'id' => $geo
        ]);

        $geoProvince = $geoRegionsRepository->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'iso2' => $geoCity->getIso2()
        ]);

        if ( $event->getPaymentStatus() == 'paid' && !empty($event->getPlan()) && !empty($event->getPaidDate()) && ($event->getPaidDate() < $now) )
        {
            $viewPremium = $entityManager->getRepository(ViewPremiumAdvert::class)->findOneBy(['eventAdvertId' => $event->getId()]);

            if ( $viewPremium )
            {
                $view = (int)$viewPremium->getViews();
                $view += 1;
            } else {
                $viewPremium = new ViewPremiumAdvert();
                $viewPremium->setEventAdvertId($event->getId());
                $view = 1;
            }

            $viewPremium->setViews($view);

            $entityManager->persist($viewPremium);
            $entityManager->flush();


        }
        /*big premium ads view count*/
        elseif (($eventAdvertPremium = $entityManager->getRepository(EventadvertPremium::class)->findOneBy([
            'redirection_link' => $event->getId()
        ]))) {
            if ( $eventAdvertPremium->getPaid() == 'paid' && !empty($eventAdvertPremium->getPlan()) && !empty($eventAdvertPremium->getPaidDate()) && ($eventAdvertPremium->getPaidDate() < $now) )
            {
                $viewPremium = $entityManager->getRepository(ViewBigPremiumAdvert::class)->findOneBy(['eventPremiumId' => $event->getId()]);

                if ( $viewPremium )
                {
                    $view = (int)$viewPremium->getViews();
                    $view += 1;
                } else {

                    $viewPremium = new ViewBigPremiumAdvert();
                    $viewPremium->setEventPremiumId($event->getId());
                    $view = 1;
                }

                $viewPremium->setViews($view);
                $entityManager->persist($viewPremium);
                $entityManager->flush();

            }
        }


        $latitude = $event->getCompany()->getLatitude();
        $longitude = $event->getCompany()->getLongitude();
        if($event->getLatitude() !== null){
            $latitude = $event->getLatitude();
        }
        if($event->getLongitude() !== null){
            $longitude = $event->getLongitude();
        }

        return $this->render('advert/advert.html.twig', [
            'channel' => $channel,
            'category' => $category,
            'event' => $event,
            'geoPlace' => $geoPlace,
            'city' => $geoCity,
            'province' => $geoProvince,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);
    }

    #[Route(path: ['en' => '/events/{category}/{slug}.html', 'nl' => '/evenementen/{category}/{slug}.html', 'fr' => '/evenements/{category}/{slug}.html'], name: 'events_advert')]
    public function eventsAdvert(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        string                $category,
        string                $slug,
        EntityManagerInterface $entityManager
    )
    {
        $event = $eventadvertRepository->findOneBy(['titleSlug' => $slug]);

        if ( !$event or $event?->getStatus() === 0 )
        {
            header('Refresh: 2; url=/');
            return $this->json(['Deze advertentie is niet meer beschikbaar']);
        } else {
            $now = new \Datetime();
            $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $event->getUserId()]);

            if ( $user->getBlocked() == true ) {
                header('Refresh: 2; url=/');
                return $this->json(['Deze advertentie is niet meer beschikbaar']);
            }

            $geo = $event->getGeoPlacesId() > 0 ? $event->getGeoPlacesId() : $event->getCompany()->getGeoPlacesId();
            // $geo = $event->getCompany()->getGeoPlacesId();

            $channel = $channelRepository->find($event->getChannel());
            $category = $categoryRepository->find($event->getCategory());

            $eventadvertRepository->processView($event->getId());

            $geoPlace = $geoPlacesRepository->findByIdAndLocale($geo, $request->getLocale());

            $locale = $request->getLocale();
            if ($locale === 'en') {
                $locale = 'nl';
            }

            $country = strtoupper($this->getParameter('country'));

            $geoCity = $geoPlacesRepository->findOneBy([
                'iso' => $country,
                'language' => $locale,
                'id' => $geo
            ]);

            $geoProvince = $geoRegionsRepository->findOneBy([
                'iso' => $country,
                'language' => $locale,
                'iso2' => $geoCity ? $geoCity->getIso2() : null
            ]);

            if ( $event->getPaymentStatus() == 'paid' && !empty($event->getPlan()) && !empty($event->getPaidDate()) && ($event->getPaidDate() < $now) )
            {
                $viewPremium = $entityManager->getRepository(ViewPremiumAdvert::class)->findOneBy(['eventAdvertId' => $event->getId()]);

                if ( $viewPremium )
                {
                    $view = (int)$viewPremium->getViews();
                    $view += 1;
                } else {
                    $viewPremium = new ViewPremiumAdvert();
                    $viewPremium->setEventAdvertId($event->getId());
                    $view = 1;
                }

                $viewPremium->setViews($view);

                $entityManager->persist($viewPremium);
                $entityManager->flush();


            }
            /*big premium ads view count*/
            elseif (($eventAdvertPremium = $entityManager->getRepository(EventadvertPremium::class)->findOneBy([
                'redirection_link' => $event->getId()
            ]))) {
                if ( $eventAdvertPremium->getPaid() == 'paid' && !empty($eventAdvertPremium->getPlan()) && !empty($eventAdvertPremium->getPaidDate()) && ($eventAdvertPremium->getPaidDate() < $now) )
                {
                    $viewPremium = $entityManager->getRepository(ViewBigPremiumAdvert::class)->findOneBy(['eventPremiumId' => $event->getId()]);

                    if ( $viewPremium )
                    {
                        $view = (int)$viewPremium->getViews();
                        $view += 1;
                    } else {

                        $viewPremium = new ViewBigPremiumAdvert();
                        $viewPremium->setEventPremiumId($event->getId());
                        $view = 1;
                    }

                    $viewPremium->setViews($view);
                    $entityManager->persist($viewPremium);
                    $entityManager->flush();

                }
            }


            $latitude = $event->getCompany()->getLatitude();
            $longitude = $event->getCompany()->getLongitude();
            if($event->getLatitude() !== null){
                $latitude = $event->getLatitude();
            }
            if($event->getLongitude() !== null){
                $longitude = $event->getLongitude();
            }

            return $this->render('advert/advert.html.twig', [
                'channel' => $channel,
                'category' => $category,
                'event' => $event,
                'geoPlace' => $geoPlace,
                'city' => $geoCity,
                'province' => $geoProvince,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);

        }
    }


    #[Route(path: ['en' => '/local-stores/{category}/{slug}.html', 'nl' => '/handelaars/{category}/{slug}.html', 'fr' => '/marchands/{category}/{slug}.html'], name: 'local_stores_advert')]
    public function storesAdvert(
        Request               $request,
        ChannelRepository     $channelRepository,
        CategoryRepository    $categoryRepository,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        GeoRegionsRepository  $geoRegionsRepository,
        string                $category,
        string                $slug,
        EntityManagerInterface $entityManager
    )
    {
        $event = $eventadvertRepository->findOneBy(['titleSlug' => $slug]);

        if ( !$event or $event?->getStatus() === 0 )
        {
            header('Refresh: 2; url=/');
            return $this->json(['Deze advertentie is niet meer beschikbaar']);
        } else {
            $now = new \Datetime();
            $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $event->getUserId()]);

            if ( $user->getBlocked() == true ) {
                header('Refresh: 2; url=/');
                return $this->json(['Deze advertentie is niet meer beschikbaar']);
            }

            $geo = $event->getGeoPlacesId() > 0 ? $event->getGeoPlacesId() : $event->getCompany()->getGeoPlacesId();
            // $geo = $event->getCompany()->getGeoPlacesId();

            $channel = $channelRepository->find($event->getChannel());
            $category = $categoryRepository->find($event->getCategory());

            $eventadvertRepository->processView($event->getId());
            $geoPlace = $geoPlacesRepository->findByIdAndLocale($geo, $request->getLocale());

            $locale = $request->getLocale();
            if ($locale === 'en') {
                $locale = 'nl';
            }

            $country = strtoupper($this->getParameter('country'));

            $geoCity = $geoPlacesRepository->findOneBy([
                'iso' => $country,
                'language' => $locale,
                'id' => $geo
            ]);

            $geoProvince = $geoRegionsRepository->findOneBy([
                'iso' => $country,
                'language' => $locale,
                'iso2' => $geoCity->getIso2()
            ]);

            if ( $event->getPaymentStatus() == 'paid' && !empty($event->getPlan()) && !empty($event->getPaidDate()) && ($event->getPaidDate() < $now) )
            {
                $viewPremium = $entityManager->getRepository(ViewPremiumAdvert::class)->findOneBy(['eventAdvertId' => $event->getId()]);

                if ( $viewPremium )
                {
                    $view = (int)$viewPremium->getViews();
                    $view += 1;
                } else {
                    $viewPremium = new ViewPremiumAdvert();
                    $viewPremium->setEventAdvertId($event->getId());
                    $view = 1;
                }

                $viewPremium->setViews($view);

                $entityManager->persist($viewPremium);
                $entityManager->flush();


            }
            /*big premium ads view count*/
            elseif (($eventAdvertPremium = $entityManager->getRepository(EventadvertPremium::class)->findOneBy([
                'redirection_link' => $event->getId()
            ]))) {
                if ( $eventAdvertPremium->getPaid() == 'paid' && !empty($eventAdvertPremium->getPlan()) && !empty($eventAdvertPremium->getPaidDate()) && ($eventAdvertPremium->getPaidDate() < $now) )
                {
                    $viewPremium = $entityManager->getRepository(ViewBigPremiumAdvert::class)->findOneBy(['eventPremiumId' => $event->getId()]);

                    if ( $viewPremium )
                    {
                        $view = (int)$viewPremium->getViews();
                        $view += 1;
                    } else {

                        $viewPremium = new ViewBigPremiumAdvert();
                        $viewPremium->setEventPremiumId($event->getId());
                        $view = 1;
                    }

                    $viewPremium->setViews($view);
                    $entityManager->persist($viewPremium);
                    $entityManager->flush();

                }
            }

            $latitude = $event->getCompany()->getLatitude();
            $longitude = $event->getCompany()->getLongitude();
            if($event->getLatitude() !== null){
                $latitude = $event->getLatitude();
            }
            if($event->getLongitude() !== null){
                $longitude = $event->getLongitude();
            }

            return $this->render('advert/advert.html.twig', [
                'channel' => $channel,
                'category' => $category,
                'event' => $event,
                'geoPlace' => $geoPlace,
                'city' => $geoCity,
                'province' => $geoProvince,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
        }
    }

    private function getData(
        $deletedUsers,
        $locale,
        $categories,
        $channelRepository,
        $categoryRepository,
        $eventadvertRepository,
        $geoPlacesRepository,
        PaginatorInterface $paginator,
        $request,
        $date_event_start_from,
        $date_event_start_to,
        $date_event_end_from,
        $date_event_end_to,
        $location,
        $radius
    )
    {
        // $eventsQb = $eventadvertRepository->getFutureEvents($categories);
        $eventsQb = $eventadvertRepository->getLatestCreatedEvents($deletedUsers, $date_event_start_from, $date_event_start_to, $date_event_end_from, $date_event_end_to, $location, $radius, $categories);

        $latestEvents = [];
        $events = [];
        $geoPlaces = [];

        if ( count($eventsQb) > 0) {
            foreach ( $eventsQb as $eventQb )
            {
                $latestEvents[] = $eventQb['events'];
            }

            $events = $paginator->paginate($latestEvents, $request->query->get('page', 1), 9);
            $geoPlacesIds = [];
            foreach ($events as $e) {
                if ($e->getGeoPlacesId() > 0) {
                    $geo = $e->getGeoPlacesId();
                    $geoPlacesIds[$geo] = $geo;
                } else {
                    $geo = $e->getCompany()->getGeoPlacesId();
                    $geoPlacesIds[$geo] = $geo;
                }
            }

            if ($latestEvents !== []) {
                $geoPlaces = $geoPlacesRepository->findByIdsAndLocale($geoPlacesIds, $locale);
            }
        }

        return [
            'events' => $events,
            'geoPlaces' => $geoPlaces,
            'channels' => $channelRepository->findAll(),
            'categories' => $categoryRepository->getFeatured($locale)
        ];
    }

    #[Route(path: ['en' => '/getMostViewedAdvertsFooter', 'nl' => '/getMostViewedAdvertsFooter', 'fr' => '/getMostViewedAdvertsFooter'], name: 'getMostViewedAdvertsFooter')]
    public function getMostViewedAdvertsFooter($deletedUsers, HelperService $helper)
    {
        $adverts = $helper->getMostViewedAdvertsFooter($deletedUsers);

        return $this->render('/adverts.html.twig', array(
            'adverts' => $adverts,
        ));
    }

    #[Route(path: ['en' => '/getMostUsedKeywordsFooter', 'nl' => '/getMostUsedKeywordsFooter', 'fr' => '/getMostUsedKeywordsFooter'], name: 'getMostUsedKeywordsFooter')]
    public function getMostUsedKeywordsFooter(HelperService $helper)
    {
        $keyworks = $helper->getMostUsedKeywordsFooter();
        return $this->render('/keywords.html.twig', array(
            'keywords' => $keyworks,
        ));
    }

    private function sortItems($element1, $element2)
    {
        $datetime1 = $element1['date']->getTimestamp();
        $datetime2 = $element2['date']->getTimestamp();
        return $datetime1 - $datetime2;
    }

    private function getMainCategoriePaidsAdverts($deletedUsers, $categoryRepository, $eventadvertRepository, $main)
    {
        $categories = [];
        $mainCategories = [
            'promoties' => ['channel' => 1],
            'evenementen' => ['channel' => 2],
            'handelaars' => ['channel' => 3]
        ];

        foreach ($categoryRepository->findBy($mainCategories[$main]) as $c) {
            $categories[] = $c->getId();
        }

        $sliderItems = [];
        $newEvents = $eventadvertRepository->getEventsPaidInCategories($deletedUsers, $categories);
        if ($newEvents !== null) {
            foreach ($newEvents as $e) {
                $tmp = [
                    'type' => 'event',
                    'date' => $e->getCreationDate(),
                    'data' => $e,

                ];
                $sliderItems[] = $tmp;
            }

            usort($sliderItems, array(
                $this,
                'sortItems'
            ));
        }

        return $sliderItems;
    }


}
