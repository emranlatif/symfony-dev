<?php

namespace App\Controller\Dashboard;

use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoryRepository;
use App\Repository\EventadvertRepository;
use App\Repository\ChannelRepository;
use App\Repository\GeoPlacesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Service\HelperService;
use Symfony\Contracts\Translation\TranslatorInterface;

class MapController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/map/', 'nl' => '/dashboard/map/', 'fr' => '/dashboard/plan-detage/'], name: 'dashboard_map')]
    public function index(Request $request, HelperService $helper, UserInterface $user, CategoryRepository $categoryRepository, EventadvertRepository $eventadvertRepository, ChannelRepository $channelRepository, GeoPlacesRepository $geoPlacesRepository, TranslatorInterface $translatorInterface)
    {
        $events = [];
        $geoPlaces = null;
        $radius = 10;
        $radius_sel = 10;
        $no_item_found_msg = $translatorInterface->trans('Kies de gewenste afstand om advertenties te zoeken');

        $radius_options_in_KM = ['10', '20', '30', '40', '50', '100', '200'];

        if ($request->get('radius') && $request->get('radius') > 0) {
            if (!in_array($request->get('radius'), $radius_options_in_KM)) {
                return $this->redirectToRoute('dashboard_map');
            }
            $radius = $radius_sel = $request->get('radius');
        }

        $location = $helper->getMyCompanyLocation();
        if (!empty($location)) {
            $events = $eventadvertRepository->findByLocationRadius($location, $radius);
            $geoPlaces = $geoPlacesRepository->getByEventsArr($events, $request->getLocale());
            if (count($events) == 0) {
                $no_item_found_msg = $translatorInterface->trans('Geen advertenties gevonden binnen geselecteerde afstand');
            }
        } else {
            $no_item_found_msg = $translatorInterface->trans('U heeft geen locatie geselecteerd voor bedrijf');
        }
        $channels = $channelRepository->findAll();
        $categories = $categoryRepository->findAll();

        return $this->render('dashboard/map/index.html.twig', ['events' => $events, 'channels' => $channels, 'categories' => $categories, 'geoPlaces' => $geoPlaces, 'radius_options_in_KM' => $radius_options_in_KM, 'radius_sel' => $radius_sel, 'no_item_found_msg' => $no_item_found_msg, 'balance' => $user->getCredits()]);
    }
}
