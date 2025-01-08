<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\CompanyPhoto;
use App\Entity\Eventadvert;
use App\Entity\GeoRegions;
use App\Entity\GeoPlaces;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Repository\EventadvertRepository;
use App\Repository\MessagesRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\KeywordsRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use GeoIp2\WebService\Client;

class HelperService
{
    private $requestStack;
    private $em;
    private $user;
    private $companyRepository;
    private $userRepository;
    private $categoryRepository;
    private $notificationRepository;
    private $messageRepository;
    private $security;
    private $assetManager;
    private $eventAdvertRepository;
    private $keywordsRepository;
    /** @var CacheManager */
    private $cacheManager;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $em,
        CompanyRepository $companyRepository,
        NotificationRepository $notificationRepository,
        Security $security,
        MessagesRepository $messageRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        Packages $assetManager,
        EventadvertRepository $eventadvertRepository,
        CacheManager           $cacheManager,
        KeywordsRepository     $keywordsRepository
    ) {
        $this->requestStack = $requestStack;
        $this->em = $em;
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->notificationRepository = $notificationRepository;
        $this->security = $security;
        $this->messageRepository = $messageRepository;
        $this->assetManager = $assetManager;
        $this->eventAdvertRepository = $eventadvertRepository;
        $this->cacheManager = $cacheManager;
        $this->keywordsRepository = $keywordsRepository;
    }

    public function getUnreadNotifications()
    {
        $user = $this->security->getUser();

        if ($user) {
            /** @var Company $company */
            $company = $this->companyRepository->findOneBy(["userId" => $user->getId()]);

            if ($company) {
                $noticationList = $this->notificationRepository->findBy(["companyId" => $company->getId(), "isRead" => 0, 'type' => Notification::EVENT]);
                return count($noticationList);
            }
        }

        return 0;
    }

    public function getUnreadMessages()
    {
        $user = $this->security->getUser();

        if ($user) {
            /** @var Company $company */
            $company = $this->companyRepository->findOneBy(["userId" => $user->getId()]);
            if ($company) {
                $messageList = $this->messageRepository->findBy(["companyId" => $company->getId(), "isRead" => 0]);
                return count($messageList);
            }
        }

        return 0;
    }

    public function getCurrentActiveAccount()
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user) {
            /** @var Company $company */
            $company = $this->companyRepository->findOneBy(["userId" => $user->getId()]);
        }

        return $company ? $company->getCompanyname() : ($user->getFirstname() . ' ' . $user->getSurname());
    }

    public function getFirstname()
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user) {
            /** @var Company $company */
            $company = $this->companyRepository->findOneBy(["userId" => $user->getId()]);
        }

        return $company ? $company->getCompanyname() : ($user->getFirstname());
    }

    public function getCompanyImage()
    {
        $mainPhoto = null;

        /** @var User $user */
        $user = $this->security->getUser();
        /** @var Company $company */
        $company = $this->companyRepository->findOneBy(["userId" => $user->getId()]);
        if ($company) {
            /** @var CompanyPhoto $company_photos */
            $company_photos = $this->em->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC'], 1);

            $mainPhoto = $company_photos ? $company_photos[0]->getImageName() : null;
        }

        return $mainPhoto;
    }

    public function getRecentAdverts()
    {
        $mostRecentList = [];

        $user = $this->security->getUser();
        $eventAdvertList = $this->eventAdvertRepository->findBy(['userId' => $user->getId()], ['eventStartDate' => 'desc'], 3);

        /** @var Eventadvert $eventadvert */
        foreach ($eventAdvertList as $eventadvert) {
            $mostRecentList[] = [
                'title' => $eventadvert->getTitle(),
                'description' => $eventadvert->getDescription(),
                'startDate' => $eventadvert->getEventStartDate()->format('d/m/Y')
            ];
        }

        return $mostRecentList;
    }

    public function getMostViewedAdverts()
    {
        $mostViewedList = [];

        $user = $this->security->getUser();
        $eventAdvertList = $this->eventAdvertRepository->findBy(['userId' => $user->getId()], ['views' => 'desc'], 3);

        /** @var Eventadvert $eventadvert */
        foreach ($eventAdvertList as $eventadvert) {
            $mostViewedList[] = [
                'title' => $eventadvert->getTitle(),
                'views' => $eventadvert->getViews()
            ];
        }

        return $mostViewedList;
    }

    public function getMostActiveAdverts()
    {
        $mostActiveList = [];

        $user = $this->security->getUser();
        $eventAdvertList = $this->eventAdvertRepository->findBy([], ['views' => 'desc'], 3);

        /** @var Eventadvert $eventadvert */
        foreach ($eventAdvertList as $eventadvert) {
            $mostActiveList[] = [
                'title' => $eventadvert->getTitle(),
                'company' => $eventadvert->getCompany()->getCompanyname(),
                'views' => $eventadvert->getViews()
            ];
        }

        return $mostActiveList;
    }

    public function getMostViewedAdvertsFooter($deletedUsers)
    {
        $mostViewedList = [];
        // $eventAdvertList = $this->eventAdvertRepository->findBy([], ['views' => 'desc'], 5);
        $eventAdvertList = $this->eventAdvertRepository->getActiveUsersMostViewedAdverts($deletedUsers);
        /** @var Eventadvert $eventadvert */
        foreach ($eventAdvertList as $eventadvert) {
            $category = $this->categoryRepository->find($eventadvert->getCategory());
            $mostViewedList[] = [
                'title' => $eventadvert->getTitle(),
                'titleslug' => $eventadvert->getTitleSlug(),
                'categorytitleslug' => $category->getTitleSlug(),
                'views' => $eventadvert->getViews()
            ];
        }
        return $mostViewedList;
    }

    public function getMyCompanyLocation()
    {
        $location = [];

        /** @var User $user */
        $user = $this->security->getUser();
        if ($user) {
            /** @var Company $company */
            $company = $this->companyRepository->findOneBy(["userId" => $user->getId()]);
            if ($company) {
                /** @var GeoPlaces $company_location */
                $company_location = $this->em->getRepository(GeoPlaces::class)->findOneBy(['id' => $company->getGeoPlacesId(), 'language' => $request = $this->requestStack->getCurrentRequest()->getLocale()]);
                if ($company_location) {
                    $location['latitude'] = $company_location->getLatitude();
                    $location['longitude'] = $company_location->getLongitude();
                }
            }
        }

        return $location;
    }

    public function getUserLocation($postcode)
    {
        $location = [];
        $limit = 1;
        $repo = $this->em->getRepository(GeoPlaces::class)->createQueryBuilder('g');
        $user_location = $repo->where('g.postcode LIKE :postcode')
            ->andWhere('g.language = :language')
            ->setParameter('postcode', '%' . $postcode . '%')
            ->setParameter('language', $request = $this->requestStack->getCurrentRequest()->getLocale())
            ->orderBy('g.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (count($user_location) > 0) {
            $location['latitude'] = $user_location[0]->getLatitude();
            $location['longitude'] = $user_location[0]->getLongitude();
        }

        return $location;
    }

    /**
     * @return array
     */
    public function getMostUsedKeywordsFooter(): array
    {
        return $this->getMostUsedKeywords(10);
    }

    /**
     * @param integer $limit
     * @return array
     */
    public function getMostUsedKeywords(int $limit = 100): array
    {
        $mostUsedList = [];
        $keywordList = $this->keywordsRepository->findMostKeywordsUsed($limit);

        foreach ($keywordList as $keyword) {
            $mostUsedList[] = [
                'name' => $keyword['name']
            ];
        }
        return $mostUsedList;
    }


    public function getListDeletedUser()
    {
        $listDeletedUser = [];
        $deletedUsers = $this->userRepository->findBy(['apStatus' => 1]);
        foreach ($deletedUsers as $deletedUser) {
            $listDeletedUser[] = $deletedUser->getId();
        }
        return $listDeletedUser;
    }
    public function getListInactiveUser()
    {
        // Fetch users with deleted = 1, blocked = 1, and enabled = 0
        $listInactiveUser = [];
        $inactiveUsers = $this->userRepository->findBy([
            'deleted' => 1,
            'blocked' => 1,
            'enabled' => 0
        ]);

        // Collect the user IDs
        foreach ($inactiveUsers as $inactiveUser) {
            $listInactiveUser[] = $inactiveUser->getId();
        }

        return $listInactiveUser;
    }

    public function canPostFreeAdvert(User $user): bool
    {
        // Allow unlimited adverts for users with special permissions
        if ($user->getAllowUnlimitedFreeAdverts() === true) {
            return true;
        }

        // Fetch the user's company
        $company = $this->em->getRepository(Company::class)->findOneBy(['userId' => $user->getId()]);
        if (!$company) {
            return false; // If no company, they cannot post adverts
        }

        // Base query builder for counting adverts
        $qb = $this->em->getRepository(Eventadvert::class)->createQueryBuilder('event_advert')
            ->select('COUNT(event_advert)')
            ->where('event_advert.company = :company')
            ->setParameter('company', $company);

        // Count adverts in the current month
        $eventsInThisMonth = (clone $qb)
            ->andWhere('event_advert.creationDate BETWEEN :start_of_month AND :end_of_month')
            ->setParameter('start_of_month', Carbon::now()->startOfMonth()->toDateString())
            ->setParameter('end_of_month', Carbon::now()->endOfMonth()->toDateString())
            ->getQuery()
            ->getSingleScalarResult();

        // Count adverts in the current week
        $eventsInThisWeek = (clone $qb)
            ->andWhere('event_advert.creationDate BETWEEN :start_of_week AND :end_of_week')
            ->setParameter('start_of_week', Carbon::now()->startOfWeek()->toDateString())
            ->setParameter('end_of_week', Carbon::now()->endOfWeek()->toDateString())
            ->getQuery()
            ->getSingleScalarResult();

        // Check limits
        return $eventsInThisMonth < 3 && $eventsInThisWeek < 1;
    }

    public function validateAdvertForEditing(Eventadvert $eventAdvert, User $user, $oldadvert): bool
    {
        if ($this->canPostFreeAdvert($user)) {
            return true;
        };
        // Fetch the current date
        $currentDate = Carbon::now();
        //if advert is created today so allow it
        if ($this->isCreatedToday($eventAdvert) || $currentDate < $oldadvert->geteventStartDate()) {
            return true;
        }


        // Get advert details
        $eventStartDate = $eventAdvert->getCreationDate();
        $eventEndDate = $eventAdvert->getEventEndDate();

        // get old envent end date
        $eventOldEndDate = $oldadvert->getEventEndDate();

        if ($eventEndDate > $eventOldEndDate) {
            return false;
        }


        $paymentStatus = $eventAdvert->getPaymentStatus();
        $status = $eventAdvert->getStatus(); // Assuming 1 = active, 0 = inactive
        // Check if the advert is active and payment is pending
        if ($status == 1 && $paymentStatus === 'pending') {
            // If the user is trying to set a future end date, disallow the edit
            if ($eventEndDate > $eventOldEndDate) {
                return false;
            }
        }

        return true; // All checks passed
    }

    private function isCreatedToday(Eventadvert $eventAdvert): bool
    {
        // Get today's date
        $today = Carbon::today();

        // Get the creation date from the advert
        $creationDate = Carbon::parse($eventAdvert->getCreationDate());

        // Compare dates (ignoring the time part)
        return $creationDate->isSameDay($today);
    }
}
