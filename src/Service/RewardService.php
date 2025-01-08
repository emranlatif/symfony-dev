<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Referred;
use App\Entity\Reward;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\Eventadvert;
use App\Entity\EventadvertPremium;

class RewardService
{
    const REWARD_SIGNUP = 10;
    const REWARD_FREE_ADVERT = 10;
    const ADVERT_REWARD = [
        'ONE_DAY' => 1,
        'FOUR_DAY' => 2,
        'SEVEN_DAY' => 3
    ];
    const PREMIUM_REWARD = [
        'ONE_WEEK' => 5,
        'TWO_WEEKS' => 10,
        'ONE_MONTH' => 15
    ];

    private $entityManager;

    private $logger;

    private $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager, LoggerInterface $logger, TokenStorageInterface $tokenStorage
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
    }

    public function checkSignup(Referred $referred){
        $parentUser = $referred->getParentUser();

        if(!$referred->getIsProcessed()){
            $referred->setIsProcessed(true);
            $this->entityManager->persist($referred);

            // give free credits to parent user
            $parentUser->setCredits($parentUser->getCredits() + self::REWARD_SIGNUP);
            $this->entityManager->persist($parentUser);

            // create reward entry
            $reward = new Reward();
            $reward->setReferred($referred);
            $reward->setCredits(self::REWARD_SIGNUP);
            $reward->setService(Reward::SERVICE_SIGNUP);
            $reward->setDescription(sprintf('%s credits toegekend door registratie van %s', self::REWARD_SIGNUP, $referred->getChildUser()->getFullName()));
            $reward->setCreatedAt(new DateTime());
            $this->entityManager->persist($reward);

            // flush changes to database
            $this->entityManager->flush();

            // add a log for admin
            $this->logger->log('INFO', sprintf('%s credits toegekend aan %s', self::REWARD_SIGNUP, $parentUser->getFullName()));
        }
    }

    public function checkFreeAdvert(){
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $referred = $this->entityManager->getRepository(Referred::class)->findOneBy([
            'childUser' => $user
        ]);

        if($referred !== null){
            if($referred->getCreatedAt()->diff(new DateTime())->days <= 180){ // check within 6 months
                $freeAdvert = $this->entityManager->getRepository(Eventadvert::class)->findOneBy([
                    'paidDate' => null,
                    'userId' => $user->getId()
                ]);
                $company = $this->entityManager->getRepository(Company::class)->findOneBy([
                    'userId' => $user->getId()
                ]);

                $reward = $this->entityManager->getRepository(Reward::class)->findOneBy([
                    'referred' => $referred,
                    'service' => Reward::SERVICE_FREE_ADVERT
                ]);

                if($reward === null && $company !== null && $freeAdvert !== null){
                    $rewardCredits = self::REWARD_FREE_ADVERT;
                    $parentUser = $referred->getParentUser();

                    $parentUser->setCredits($parentUser->getCredits() + $rewardCredits);
                    $this->entityManager->persist($parentUser);

                    $reward = new Reward();
                    $reward->setCredits($rewardCredits);
                    $reward->setDescription(sprintf('%s credits door activatie van gebruiker %s', $rewardCredits, $user->getId()));
                    $reward->setCreatedAt(new DateTime());
                    $reward->setReferred($referred);
                    $reward->setService(Reward::SERVICE_FREE_ADVERT);
                    $this->entityManager->persist($reward);

                    $this->entityManager->flush();

                    $this->logger->log('INFO', sprintf('%s credits toegekend %s voor gebruiker %s', $rewardCredits, $parentUser->getFullName(), $user->getId()));
                }
            }
        }
    }

    public function checkBigPremiumAdvert(EventadvertPremium $advert){
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $referred = $this->entityManager->getRepository(Referred::class)->findOneBy([
            'childUser' => $user
        ]);

        if($referred !== null){
            if($referred->getCreatedAt()->diff(new DateTime())->days <= 180){ // check within 6 months
                $rewardCredits = self::PREMIUM_REWARD[$advert->getPlan()];

                $parentUser = $referred->getParentUser();

                $parentUser->setCredits($parentUser->getCredits() + $rewardCredits);
                $this->entityManager->persist($parentUser);

                $reward = new Reward();
                $reward->setCredits($rewardCredits);
                $reward->setDescription(sprintf('%s credits door aankoop big premium %s by %s', $rewardCredits, $advert->getId(), $user->getFullName()));
                $reward->setCreatedAt(new DateTime());
                $reward->setReferred($referred);
                $this->entityManager->persist($reward);

                $this->entityManager->flush();

                $this->logger->log('INFO', sprintf('%s credits toegekend %s voor big premium (%s)', $rewardCredits, $parentUser->getFullName(), $advert->getId()));
            }
        }
    }

    public function checkPremiumAdvert(Eventadvert $advert){
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $referred = $this->entityManager->getRepository(Referred::class)->findOneBy([
            'childUser' => $user
        ]);

        if($referred !== null){
            if($referred->getCreatedAt()->diff(new DateTime())->days <= 180){ // check within 6 months
                $rewardCredits = self::ADVERT_REWARD[$advert->getPlan()] ?? 0;

                $parentUser = $referred->getParentUser();

                $parentUser->setCredits($parentUser->getCredits() + $rewardCredits);
                $this->entityManager->persist($parentUser);

                $reward = new Reward();
                $reward->setCredits($rewardCredits);
                $reward->setDescription(sprintf('%s credits door aankoop premium %s by %s', $rewardCredits, $advert->getId(), $user->getFullName()));
                $reward->setCreatedAt(new DateTime());
                $reward->setReferred($referred);
                $this->entityManager->persist($reward);

                $this->entityManager->flush();

                $this->logger->log('INFO', sprintf('%s credits toegekend %s voor premium (%s)', $rewardCredits, $parentUser->getFullName(), $advert->getId()));
            }
        }
    }
}
