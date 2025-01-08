<?php

namespace App\Service;

use App\Entity\Eventadvert;
use App\Entity\GeoRegions;
use App\Entity\Notification;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class NotificationService
{
    private $em;
    private $mailer;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->mailer = $mailer;
    }

    public function checkEndDate($numberOfDays)
    {
        $current_date = date("Y-m-d");

        $qb = $this->em->createQueryBuilder();

        $qb->select('e')
            ->from(Eventadvert::class, 'e')
            ->where(
                "DATE_SUB(e.eventEndDate, :numberOfDays, 'DAY') <= :current_date"
            );

        $qb->setParameter(':numberOfDays', $numberOfDays);
        $qb->setParameter(':current_date', $current_date);

        $eventList = $qb->getQuery()->getResult();

        /** @var Eventadvert $event */
        foreach ($eventList as $event) {
            $notification = new Notification();

            $notification->setType(Notification::EVENT);
            $notification->setObj($event->getId());
            $notification->setIsRead(0);
            $notification->setCompanyId($event->getCompany()->getId());

            $this->em->persist($notification);
            $this->em->flush();
        }
    }

    public function handleAdvertEndDate()
    {
        $qb = $this->em->createQueryBuilder();
        $now = new Datetime;

        $qb->select('e')
            ->from(Eventadvert::class, 'e')
            ->where(':from < e.eventEndDate')
            ->andWhere('e.eventEndDate <= :to'
            );

        $qb->setParameter('from', new DateTime('-2 days'));
        $qb->setParameter('to', new DateTime());

        $eventList = $qb->getQuery()->getResult();

        /** @var Eventadvert $event */
        foreach ($eventList as $event) {
            $notification = new Notification();

            $notification->setType(Notification::EVENT);
            $notification->setObj($event->getId());
            $notification->setIsRead(0);
            $notification->setCompanyId($event->getCompany()->getId());

            $this->em->persist($notification);
            $this->em->flush();

            $user = $this->em->getRepository(User::class)->find($event->getUserId());
           
            $subject = 'Einddatum Advertentie';

            if ( $user->getEnabled() == 1 && $user->getSendNotifications() == 1 && $user->getDeleted() != 1 && $user->getBlocked() != 1 )
            {
                $userMail = $user->getEmail();
                $this->sendMailEndingDateAdvert($this->mailer, $userMail, $subject, $event);
            } 
        }
    }

    public function sendMailEndingDateAdvert($mailer, $user, $subject, $eventAdvert)
    {
        try {

            $publicUrl = $_SERVER['ASSETS_URL_PUBLIC'];
            $payUrl = $publicUrl . 'dashboard/event/' . $eventAdvert->getId() . '/pay';

            $email = (new TemplatedEmail())
                ->to($user)
                ->subject($subject)
                ->htmlTemplate('emails/html/endingdate_advert.html.twig')
                ->context([
                    'eventAdvert' => $eventAdvert,
                    'url' => $payUrl
                ]);

            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }

    public function sendMailActivatePremiumAdvert($mailer, $user, $subject, $eventAdvert)
    {
        try {
            $publicUrl = $_SERVER['ASSETS_URL_PUBLIC'];
            $payUrl = $publicUrl . 'dashboard/event/' . $eventAdvert->getId() . '/pay';

            $email = (new TemplatedEmail())
                ->to($user)
                ->subject($subject)
                ->htmlTemplate('emails/html/advert_premium.html.twig')
                ->context([
                    'eventAdvert' => $eventAdvert,
                    'url' => $payUrl
                ]);

            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }

    public function sendMailInfoActivatePremiumAdvert($mailer, $user, $subject, $eventAdvert)
    {
        try {
            $publicUrl = $_SERVER['ASSETS_URL_PUBLIC'];
            $payUrl = $publicUrl . 'dashboard/event/' . $eventAdvert->getId() . '/pay';

            $email = (new TemplatedEmail())
                ->to($user)
                ->subject($subject)
                ->htmlTemplate('emails/html/advert_info_premium.html.twig')
                ->context([
                    'eventAdvert' => $eventAdvert,
                    'url' => $payUrl
                ]);

            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }

    public function sendMailBasicEmail($mailer, $user, $subject)
    {
        try {

            $publicUrl = $_SERVER['ASSETS_URL_PUBLIC'];
            $loginUrl = $publicUrl . 'login';

            $email = (new TemplatedEmail())
                ->to($user)
                ->subject($subject)
                ->htmlTemplate('emails/html/we_miss.html.twig')
                ->context([
                    'url' => $loginUrl
                ]);

            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }

    /**
     * @param MailerInterface $mailer
     * @param string $user
     * 
     * @return void
     */
    public function sendNewMessageReceivedEmail(MailerInterface $mailer, string $user): void
    {
        try {
            $subject = 'Nieuw bericht ontvangen in uw dashboard';
            $email = (new TemplatedEmail())
                ->to($user)
                ->subject($subject)
                ->htmlTemplate('emails/html/new_message_received.html.twig')
                ->context(compact('subject'));

            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }
}