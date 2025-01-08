<?php

namespace App\Controller\Dashboard;

use App\Entity\Referred;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use App\Repository\EventadvertRepository;
use App\Repository\GeoPlacesRepository;
use App\Service\RewardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @return Response
     *
     */
    #[Route(path: ['en' => '/dashboard/', 'nl' => '/dashboard/', 'fr' => '/dashboard/'], name: 'dashboard_index')]
    public function index(
        Request               $request, UserInterface $user,
        EventadvertRepository $eventadvertRepository,
        CategoryRepository    $categoryRepository,
        ChannelRepository     $channelRepository,
        GeoPlacesRepository   $geoPlacesRepository,
        RewardService $rewardService
    )
    {
        $channels = $channelRepository->findAll();
        $categories = $categoryRepository->findAll();
        $qb = $eventadvertRepository->createQueryBuilder('eventadvert');
        $qb->andWhere('eventadvert.userId = :user')->setParameter('user', $user->getId());
        $qb->andWhere('eventadvert.deleted IS NULL OR eventadvert.deleted = :false');
        $qb->setParameter('false', false);

        $events = $qb->getQuery()->getResult();

        $geoPlaces = $geoPlacesRepository->getByEvents($events, $request->getLocale());

        $rewardService->checkFreeAdvert();

        return $this->render(
            'dashboard/index/index.html.twig',
            [
                'events' => $events,
                'channels' => $channels,
                'categories' => $categories,
                'geoPlaces' => $geoPlaces,
                'ac' => $request->get('ac'),
                'balance' => $user->getCredits()
            ]
        );
    }
}


