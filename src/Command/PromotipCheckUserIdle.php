<?php

namespace App\Command;

use App\Service\NotificationService;
use Datetime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Entity\Eventadvert;

#[AsCommand(
    name: "promotip:check-user-idle",
    description: "Check user idle",
)]
class PromotipCheckUserIdle extends Command
{
    protected static $defaultName = 'promotip:check-user-idle';

    private $notifManager;
    private $entityManager;
    private $userRepository;
    private $mailer;
    private $helper;

    public function __construct(
        NotificationService    $notifManager,
        MailerInterface        $mailer,
        EntityManagerInterface $entityManager,
        UserRepository         $userRepository
    )
    {
        $this->notifManager = $notifManager;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subject = 'We miss you come back';
        $now = new Datetime();

        $entityManager = $this->entityManager;
        $activesUser = $this->userRepository->getActivesUser();

        if (count($activesUser) > 0) {
            foreach ($activesUser as $activeUser) {
                $now = new Datetime();
                if ( $activeUser->getLastLogin() != null )
                {
                    $dateMin = new Datetime(date('Y-m-d H:i', strtotime('+3 months', strtotime($activeUser->getLastLogin()->format('Y-m-d H:i:s')))));
                    $dateMax = new Datetime(date('Y-m-d H:i', strtotime('+3 months 1 day', strtotime($activeUser->getLastLogin()->format('Y-m-d H:i:s')))));

                    if ($dateMin <= $now && $dateMax >= $now) {
                            $userMail = $activeUser->getEmail();

                            $userAdverts = $entityManager->getRepository(Eventadvert::class)->findBy([
                                'userId' => $activeUser->getId()
                            ]);

                            if ( $userAdverts )
                            {
                                foreach ( $userAdverts as $userAdvert )
                                {
                                    $userAdvert->setPaused(true);
                                    $entityManager->flush();
                                }
                            }

                            if ( $activeUser->getSendNotifications() == 1 ) {
                                $this->notifManager->sendMailBasicEmail($this->mailer, $userMail, $subject);
                            }
                    }
                } else {
                    $userMail = $activeUser->getEmail();

                    $userAdverts = $entityManager->getRepository(Eventadvert::class)->findBy([
                        'userId' => $activeUser->getId()
                    ]);

                    if ( $userAdverts )
                    {
                        foreach ( $userAdverts as $userAdvert )
                        {
                            $userAdvert->setPaused(true);
                            $entityManager->flush();
                        }
                    }

                    if ( $activeUser->getSendNotifications() == 1 ) {
                        $this->notifManager->sendMailBasicEmail($this->mailer, $userMail, $subject);
                    }
                }
            }
        }

        return 0;
    }
}
