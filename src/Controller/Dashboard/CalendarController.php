<?php

namespace App\Controller\Dashboard;

use App\Entity\Company;
use App\Entity\Eventadvert;
use App\Repository\EventadvertRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;


class CalendarController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/calendar/', 'nl' => '/dashboard/kalender/', 'fr' => '/dashboard/calendrier/'], name: 'dashboard_calendar')]
    public function index(Request $request, UserInterface $user, CompanyRepository $companyRepository)
    {
        return $this->render('dashboard/calendar/index.html.twig', ['balance' => $user->getCredits()]);
    }

    #[Route(path: ['en' => '/dashboard/calendar/events', 'nl' => '/dashboard/kalender/events', 'fr' => '/dashboard/calendrier/events'], name: 'dashboard_calendar_events')]
    public function events(Request $request, UserInterface $user, CompanyRepository $companyRepository, EventadvertRepository $eventadvertRepository)
    {
        $calendarEvents = [];
        $userId = $user->getId();
        $companyRepository->findOneBy(['userId' => $userId]);
        $eventList = $eventadvertRepository->findBy(['userId' => $userId]);

        /** @var Eventadvert $eventadvert */
        foreach ($eventList as $eventadvert) {
            $calendarEvents[] = [
                "id" => $eventadvert->getId(),
                'title' => $eventadvert->getTitle(),
                'start' => $eventadvert->getEventStartDate()->format('Y-m-d') . ' ' . $eventadvert->getStartHour()->format('H:i:s'),
                'end' => $eventadvert->getEventEndDate()->format('Y-m-d') . ' ' . $eventadvert->getEndHour()->format('H:i:s'),
                'backgroundColor' => '#a7517b',
                'borderColor' => '#854062',
                'textColor' => '#ffffff',
                'allDay' => false,
                'balance' => $user->getCredits(),
                'extendedProps' => [
                ]
            ];
        }

        return $this->json($calendarEvents);
    }
}
