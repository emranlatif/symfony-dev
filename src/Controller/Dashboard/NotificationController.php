<?php

namespace App\Controller\Dashboard;

use App\Entity\Company;
use App\Entity\Eventadvert;
use App\Entity\Notification;
use App\Repository\EventadvertRepository;
use App\Repository\NotificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class NotificationController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/notifications/', 'nl' => '/dashboard/meldingen/', 'fr' => '/dashboard/notifications/'], name: 'dashboard_notification')]
    public function index(
        Request                $request,
        UserInterface          $user,
        CompanyRepository      $companyRepository,
        NotificationRepository $notificationRepository,
        EventadvertRepository  $eventadvertRepository,
        TranslatorInterface    $translatorInterface
    )
    {
        $notificationData = [];
        $today = new DateTimeImmutable();

        /** @var Company $company */
        $company = $companyRepository->findOneBy(["userId" => $user->getId()]);

        if (is_null($company)) {
            return $this->redirectToRoute('dashboard_company', ['cf' => 'meldingen']);
        }

        $noticationList = $notificationRepository->findBy(["companyId" => $company->getId(), "isRead" => 0]);

        /** @var Notification $notification */
        foreach ($noticationList as $notification) {
            if ($notification->getType() == Notification::EVENT) {
                $event = $eventadvertRepository->find($notification->getObj());
                $dti = $today->diff($event->getEventEndDate());
                $days = 1;
                if (!$dti->invert) {
                    $days = $dti->days;
                }
                $msg = sprintf($translatorInterface->trans("event_message", ['notifications' => $days]), $translatorInterface->trans("event"), $event->getTitle());
                $notificationData[] = ["id" => $notification->getId(), "notId" => $notification->getObj(), "msg" => $msg];
            }
        }

        return $this->render('dashboard/notification/index.html.twig', [
            "notificationList" => $notificationData,
            'balance' => $user->getCredits()
        ]);
    }

    #[Route(path: ['en' => '/dashboard/notifications/mark', 'nl' => '/dashboard/meldingen/mark', 'fr' => '/dashboard/notifications/mark'], name: 'dashboard_notification_mark_as_read')]
    public function markNotificationAsRead(Request $request, NotificationRepository $notificationRepository, EntityManagerInterface $em)
    {
        $notificationId = $request->get("notificationId");

        /** @var Notification $notification */
        $notification = $notificationRepository->find($notificationId);

        $notification->setIsRead(1);

        $em->persist($notification);
        $em->flush();

        return new JsonResponse();
    }

    public function getUnreadCount()
    {
        return 1000;
    }
}
