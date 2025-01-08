<?php

namespace App\Command;

use App\Service\NotificationService;
use App\Service\HelperService;
use Datetime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use App\Repository\EventadvertRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

#[AsCommand(
    name: "promotip:check-end-advert-premium",
    description: "Check end of premium advert and send link to renew it"
)]
class PromotipCheckEndAdvertPremiumCommand extends Command
{
    private $notifManager;
    private $eventAdvertRepository;
    private $entityManager;
    private $mailer;
    private $helper;

    private $planDays = [
        'ONE_DAY' => '+1 day',
        'FOUR_DAY' => '+4 day',
        'SEVEN_DAY' => '+7 day',
        'CREDIT_ONE_DAY' => '+1 day',
        'CREDIT_FOUR_DAY' => '+4 day',
        'CREDIT_SEVEN_DAY' => '+7 day',
        'ONE_WEEK' => '+1 week',
        'TWO_WEEKS' => '+2 week',
        'ONE_MONTH' => '+1 month'
    ];

    public function __construct(
        NotificationService    $notifManager,
        MailerInterface        $mailer,
        EventadvertRepository  $eventAdvertRepository,
        EntityManagerInterface $entityManager,
        HelperService          $helper
    )
    {
        $this->notifManager = $notifManager;
        $this->eventAdvertRepository = $eventAdvertRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->helper = $helper;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subject = 'Promotip.be premium';
        $now = new Datetime();
        $deletedUsers = $this->helper->getListDeletedUser();

        $entityManager = $this->entityManager;
        $paidAdverts = $this->eventAdvertRepository->getPaidAdvertWithDatePaid($deletedUsers);

        if (count($paidAdverts) > 0) {
            foreach ($paidAdverts as $paidAdvert) {
                $plan = $paidAdvert->getPlan();
                $paidDate = $paidAdvert->getPaidDate();
                $otherPaidDate = $paidAdvert->getPaidDate();
                $expiredDate = $paidDate;

                $now = new Datetime();

                if ($expiredDate <= $now) {
                    $user = $entityManager->getRepository(User::class)->find($paidAdvert->getUserId());

                    $paidAdvert->setPaymentStatus('pending');
                    $paidAdvert->setPlan(NULL);
                    $paidAdvert->setPaidDate(NULL);

                    $entityManager->persist($paidAdvert);
                    $entityManager->flush();

                    if ( $user->getEnabled() == 1 && $user->getSendNotifications() == 1 && $user->getDeleted() != 1 && $user->getBlocked() != 1  )
                    {
                        $userMail = $user->getEmail();
                        $this->notifManager->sendMailActivatePremiumAdvert($this->mailer, $userMail, $subject, $paidAdvert);
                    }

                } else {

                    $dateMin = new Datetime(date('Y-m-d H:i', strtotime('-4 hour', strtotime($expiredDate->format('Y-m-d H:i:s')))));
                    $dateMax = new Datetime(date('Y-m-d H:i', strtotime('-4 hour +20 minutes', strtotime($expiredDate->format('Y-m-d H:i:s')))));

                    if ($dateMin <= $now && $dateMax >= $now) {
                        $user = $entityManager->getRepository(User::class)->find($paidAdvert->getUserId());

                        if ( $user->getEnabled() == 1 && $user->getSendNotifications() == 1 && $user->getDeleted() != 1 && $user->getBlocked() != 1 )
                        {
                            $userMail = $user->getEmail();
                            $this->notifManager->sendMailInfoActivatePremiumAdvert($this->mailer, $userMail, $subject, $paidAdvert);
                        }
                    }
                }
            }
        }

        return 0;
    }
}
