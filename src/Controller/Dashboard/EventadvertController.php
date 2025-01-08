<?php

namespace App\Controller\Dashboard;

use App\Entity\Company;
use App\Entity\Eventadvert;
use App\Entity\EventadvertPhoto;
use App\Entity\EventadvertPremium;
use App\Entity\EventadvertTag;
use App\Entity\PremiumEventadvertPhoto;
use App\Entity\Tag;
use App\Entity\User;
use App\Form\EventadvertFormType;
use App\Form\PremiumEventadvertFormType;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use App\Repository\CompanyRepository;
use App\Repository\EventadvertPremiumRepository;
use App\Repository\EventadvertRepository;
use App\Repository\GeoPlacesRepository;
use App\Service\HelperService;
use Asika\Autolink\Autolink;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class EventadvertController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/event/', 'nl' => '/dashboard/evenement/', 'fr' => '/dashboard/evenement/'], name: 'dashboard_event')]
    /**
     * @var $user User
     */
    public function create(
        Request                  $request,
        UserInterface            $user,
        GeoPlacesRepository      $geoPlacesRepository,
        ChannelRepository        $channelRepository,
        CategoryRepository       $categoryRepository,
        SessionInterface         $session
        , EntityManagerInterface $entityManager,
        HelperService $helperService
    )
    {
        $eventadvertPhotos = [];

        $company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()]);

        if (is_null($company)) {
            return $this->redirectToRoute('dashboard_company', ['cf' => 'adverteren']);
        }

        $canPostFreeAdvert = $helperService->canPostFreeAdvert($user);
 
        if (($eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy([
            'userId' => $user->getId(),
            'id' => $request->get('id')
        ]))) {
            $eventadvertPhotos = $entityManager->getRepository(EventadvertPhoto::class)->findBy([
                'eventAdvert' => $request->get('id')
            ], ['priority' => 'ASC']);

            // allow in edit mode
            $canPostFreeAdvert = true;
        } else {
            $eventAdvert = new Eventadvert();
        }
      
        $oldAdvertData = clone $eventAdvert;
  
        $form = $this->createForm(EventadvertFormType::class, $eventAdvert, [
            'startDate' => $eventAdvert->getEventStartDate(),
            'endDate' => $eventAdvert->getEventEndDate(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Eventadvert $eventAdvert */
        
            $eventAdvert = $form->getData();

            //validate free ads date extend trick
            if(!$helperService->validateAdvertForEditing($eventAdvert, $user, $oldAdvertData)){
                return $this->redirectToRoute('dashboard_eventadvert_upsale', [
                    'id' => $eventAdvert->getId(),
                    'type' => 'date-change',
                ]);
                return $this->redirectToRoute('dashboard_eventadvert_upsale', ['id' => $eventAdvert->getId()]);
            }
            
            $advertDescription = $eventAdvert->getDescription();
            $autolink = new Autolink();

            $options = [
                'strip_scheme' => false,
                'text_limit' => false,
                'auto_title' => false,
                'escape' => true,
                'link_no_scheme' => true
            ];

            $schemes = ['http', 'https', 'skype', 'itunes'];

            $autolink = new Autolink($options, $schemes);

            $advertDescription = $autolink->convert($advertDescription);
            $advertDescription = $autolink->convertEmail($advertDescription);

            $eventAdvert->setDescription($advertDescription);

            $eventAdvert->setUserId($user->getId());
            $eventAdvert->setUser($user);
            $eventAdvert->setCompany($company);
            $eventAdvert->setCreationDate(new DateTime());
            $eventAdvert->setCreationIpaddr($request->getClientIp());
            $eventAdvert->setStatus(1);
            $eventAdvert->setPlan('ONE_MONTH');
            if ($request->request->get('all_day_event') !== null) {
                $eventAdvert->setAllDayEvent(1);
                $eventAdvert->setEventStartDate(null);
                $eventAdvert->setEventEndDate(null);
                $eventAdvert->setStartHour(null);
                $eventAdvert->setEndHour(null);
            } else {
                $eventAdvert->setAllDayEvent(0);
            }
            //$eventAdvert->setTranslatableLocale($request->getLocale());

            $enrichment = '';
            // Enrich channel
            if ($eventAdvert->getChannel() > 0) {
                $channel = $channelRepository->findById($eventAdvert->getChannel());
                $enrichment .= $channel[0]->getName() . ' ';
            }

            // Enrich category
            if ($eventAdvert->getCategory() > 0) {
                $category = $categoryRepository->findById($eventAdvert->getCategory());
                $enrichment .= $category[0]->getTitle() . ' ';
            }

            // Enrich ZIP + City
            if ($eventAdvert->getGeoPlacesId() > 0) {
                if ($data = $geoPlacesRepository->findByIdAndLocale($eventAdvert->getGeoPlacesId(), $request->getLocale())) {
                    $enrichment .= $data[0]->getPostcode() . ' ' . $data[0]->getLocality() . ' ';
                }
            } elseif ($company->getGeoplacesId() !== null) {
                if ($data = $geoPlacesRepository->findByIdAndLocale($company->getGeoplacesId(), $request->getLocale())) {
                    $enrichment .= $data[0]->getPostcode() . ' ' . $data[0]->getLocality() . ' ';
                }
            }

            $eventAdvert->setEnrichment($enrichment);
            if (empty($eventAdvert->getViews())) {
                $eventAdvert->setViews(0);
            }

            $entityManager->persist($eventAdvert);
            $entityManager->flush();

            // save Tag
            if ($tags = $entityManager->getRepository(EventadvertTag::class)->findBy(['advert' => $eventAdvert])) {
                foreach ($tags as $t) {
                    $entityManager->remove($t);
                    $entityManager->flush();
                }
            }

            $tags = '';
            if ($request->request->all('tags') !== null && is_array($request->request->all('tags'))) {
                foreach ($request->request->all('tags') as $t) {
                    $t = trim($t);
                    $t = strip_tags($t);

                    if ((is_numeric($t) && $t > 0) || (is_string($t) && strlen($t) > 0)) {
                        $slugger = new AsciiSlugger();
                        $tagSlug = $slugger->slug($t);

                        $tagResult = $entityManager->getRepository(Tag::class)->findOneBy(['nameSlug' => $tagSlug]);

                        if ($tagResult == false) {
                            $tagResult = new Tag();
                        }

                        $repository = $entityManager->getRepository('Gedmo\Translatable\Entity\Translation');
                        $tagResult->setTranslatableLocale($request->getLocale());
                        $repository->translate($tagResult, 'name', $request->getLocale(), $t);

                        $tagResult->setName($t);
                        $entityManager->persist($tagResult);
                        $entityManager->flush();


                        $tag = new EventadvertTag();
                        $tag->setAdvert($eventAdvert);
                        $tag->setTag($tagResult);

                        $entityManager->persist($tag);
                        $entityManager->flush();

                    }
                }
            }

            if (($photosSession = $session->get('photos_eventadvert', false)) != false) {
                foreach ($photosSession as $ph) {
                    if (($eventadvertPhoto = $entityManager->getRepository(EventadvertPhoto::class)->findOneBy([
                            'temporaryId' => $ph['temporaryId']
                        ])) != null) {
                        $eventadvertPhoto->setEventAdvert($eventAdvert);
                        $entityManager->persist($eventadvertPhoto);
                        $entityManager->flush();
                    }
                }
                $session->clear();
            }
            
            // if (!$helperService->canReactivateAdvert($eventAdvert, $user)) {
            //     return $this->redirectToRoute('dashboard_eventadvert_upsale', ['id' => $eventAdvert->getId()]);
            // }
            if(!$canPostFreeAdvert){
                // only disable status when not in edit mode
                if(!$request->query->has('id')) {
                    $eventAdvert->setStatus(0);
                }
                $entityManager->persist($eventAdvert);
                $entityManager->flush();
                //$this->addFlash('warning', 'You have reached the limit of free adverts. You will have to pay &euro; 1 to continue displaying this advert.');
                return $this->redirectToRoute('dashboard_eventadvert_upsale', ['id' => $eventAdvert->getId()]);
            }
            
            //redirect to the dashboard
            return $this->redirectToRoute('dashboard_index');
        
        } elseif ($form->isSubmitted()) {
            if (($photosSession = $session->get('photos_eventadvert', false)) != false) {
                foreach ($photosSession as $ph) {
                    $eventadvertPhotos[] = $entityManager->getRepository(EventadvertPhoto::class)->findOneBy([
                        'temporaryId' => $ph['temporaryId']
                    ]);
                }
            }
        }

        $tags = $entityManager->getRepository(EventadvertTag::class)->findBy(['advert' => $eventAdvert]);


        return $this->render('dashboard/eventadvert/index.html.twig', [
            'form' => $form->createView(),
            'eventAdvert' => $eventAdvert,
            'company' => $company,
            'tags' => $tags,
            'eventadvertPhotos' => $eventadvertPhotos,
            'ac' => $request->get('ac'),
            'showFooter' => -1,
            'balance' => $user->getCredits(),
            'companyprofile' => $request->query->get('cp'),
            'canPostFreeAdvert' => $canPostFreeAdvert
        ]);
    }

    #[Route(path: ['en' => '/dashboard/event/active', 'nl' => '/dashboard/evenement/active', 'fr' => '/dashboard/evenement/active'], name: 'dashboard_event_active')]
    public function userEventAdvertsActives(
        Request               $request, UserInterface $user,
        EventadvertRepository $eventadvertRepository,
        CategoryRepository    $categoryRepository,
        ChannelRepository     $channelRepository,
        GeoPlacesRepository   $geoPlacesRepository
    )
    {
        $channels = $channelRepository->findAll();
        $categories = $categoryRepository->findAll();
        $events = $eventadvertRepository->findUserActiveAdverts($user);

        $geoPlaces = $geoPlacesRepository->getByEvents($events, $request->getLocale());

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

    #[Route(path: ['en' => '/dashboard/event/inactive', 'nl' => '/dashboard/evenement/inactive', 'fr' => '/dashboard/evenement/inactive'], name: 'dashboard_event_inactive')]
    public function userEventAdvertsInActives(
        Request               $request, UserInterface $user,
        EventadvertRepository $eventadvertRepository,
        CategoryRepository    $categoryRepository,
        ChannelRepository     $channelRepository,
        GeoPlacesRepository   $geoPlacesRepository
    )
    {
        $channels = $channelRepository->findAll();
        $categories = $categoryRepository->findAll();
        $events = $eventadvertRepository->findUserInActiveAdverts($user);

        $geoPlaces = $geoPlacesRepository->getByEvents($events, $request->getLocale());

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

    #[Route(path: ['en' => '/dashboard/event/delete', 'nl' => '/dashboard/evenement/verwijder', 'fr' => '/dashboard/evenement/supprimer'], name: 'dashboard_eventadvert_delete')]
    public function remove(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {
        if ($eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->get('id'), 'userId' => $user->getId()])) {
            $eventAdvert->setDeleted(true);
            $eventAdvert->setDeletedAt(new \DateTime());

            $entityManager->persist($eventAdvert);
//            $entityManager->remove($eventAdvert);
            $entityManager->flush();
        }

        return $this->redirectToRoute('dashboard_index', ['ac' => 'del-advert', 'balance' => $user->getCredits()]);
    }


    #[Route(path: ['en' => '/dashboard/event/photos', 'nl' => '/dashboard/evenement/fotos', 'fr' => '/dashboard/evenement/photos'], name: 'dashboard_eventadvert_photos')]
    public function photos(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {

        $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy([
            'id' => $request->get('id'),
            'userId' => $user->getId()
        ]);

        $eventadvertPhotos = $entityManager->getRepository(EventadvertPhoto::class)->findBy(['eventAdvert' => $eventAdvert], ['priority' => 'ASC']);


        return $this->render('dashboard/eventadvert/photos.html.twig', [
            'eventadvertPhotos' => $eventadvertPhotos,
            'eventAdvert' => $eventAdvert,
            'balance' => $user->getCredits(),
        ]);
    }

    #[Route(path: ['en' => '/dashboard/event/delete-photo', 'nl' => '/dashboard/evenement/verwijder-foto', 'fr' => '/dashboard/evenement/supprimer-photo'], name: 'dashboard_eventadvert_photos_delete')]
    public function photoDelete(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {
        if (($photo = $entityManager->getRepository(EventadvertPhoto::class)->findOneBy(['id' => $request->get('id')])) && $photo->getEventAdvert()->getUserId() == $user->getId()) {
            $photoId = $photo->getId();
            $entityManager->remove($photo);
            $entityManager->flush();
            return new JsonResponse([
                'success' => true,
                'id' => $photoId
            ]);
        }

        return new JsonResponse(false);
    }

    #[Route(path: ['en' => '/dashboard/event/move-photo', 'nl' => '/dashboard/evenement/verplaats-foto', 'fr' => '/dashboard/evenement/bouger-photo'], name: 'dashboard_eventadvert_photos_move')]
    public function photoMove(Request $request, EntityManagerInterface $entityManager)
    {
        $order = $request->get('order');

        foreach ($order as $key => $photoId) {
            if ($photo = $entityManager->getRepository(EventadvertPhoto::class)->find($photoId)) {
                $photo->setPriority(($key + 1));
                $entityManager->persist($photo);
                $entityManager->flush();
            }
        }

        return new JsonResponse(true);
    }

    #[Route(path: ['en' => '/dashboard/event/photo', 'nl' => '/dashboard/evenement/foto', 'fr' => '/dashboard/evenement/photo'], name: 'dashboard_eventadvert_photo_template')]
    public function photoTemplate(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {
        $photoReturn = false;

        if (($photo = $entityManager->getRepository(EventadvertPhoto::class)->findOneBy(['temporaryId' => $request->get('temp')])) && ($eventadvert = $entityManager->getRepository(Eventadvert::class)->findOneBy([
                'id' => $photo->getEventadvert()->getId(),
                'userId' => $user->getId()
            ]))) {
            $photoReturn = $photo;
        }

        return $this->render('dashboard/eventadvert/photo.html.twig', [
            'photo' => $photoReturn,
            'balance' => $user->getCredits()
        ]);
    }

    #[Route(path: ['en' => '/dashboard/event/upsale/{id}', 'nl' => '/dashboard/evenement/upsale/{id}', 'fr' => '/dashboard/evenement/upsale/{id}'], name: 'dashboard_eventadvert_upsale', requirements: ['id' => '\d+'])]
    public function upsale(
        Request $request, UserInterface $user, EntityManagerInterface $entityManager,
        int     $id, HelperService $helperService
    )
    {
        $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy([
            'id' => $id
        ]);

        $canPostFreeAdvert = $helperService->canPostFreeAdvert($user) || $eventAdvert->getStatus() === 1 ;
        $type = $request->query->get('type');
        if(isset($type) && $type == 'date-change'){
            $canPostFreeAdvert = false;
        }
        $userCredit = $user->getCredits();
        return $this->render('dashboard/eventadvert/upsale.html.twig', [
            'eventAdvert' => $eventAdvert,
            'balance' => $userCredit,
            'canPostFreeAdvert' => $canPostFreeAdvert,
            'userCredit' => $userCredit,
            'minimumBalance' => 1,
        ]);
    }

    #[Route(path: ['en' => '/dashboard/event/big-premium/create', 'nl' => '/dashboard/evenement/big-premium/create', 'fr' => '/dashboard/evenement/big-premium/create'], name: 'create_big_premium')]
    public function createBigPremium(
        Request               $request,
        UserInterface         $user,
        EventadvertRepository $eventadvertRepository,
        SessionInterface      $session, EntityManagerInterface $entityManager
    )
    {
        $premiumEventadvertPhotos = [];

        if (($eventAdvertPremium = $entityManager->getRepository(EventadvertPremium::class)->findOneBy([
            'userId' => $user->getId(),
            'id' => $request->get('id')
        ]))) {
            $premiumEventadvertPhotos = $entityManager->getRepository(PremiumEventadvertPhoto::class)->findBy([
                'premiumEventAdvert' => $request->get('id')
            ], ['priority' => 'ASC']);
        } else {
            $eventAdvertPremium = new EventadvertPremium();
        }

        $form = $this->createForm(PremiumEventadvertFormType::class, $eventAdvertPremium);
        $form->handleRequest($request);

        $adverts = $eventadvertRepository->findBy([
            'userId' => $user->getId(),
            'paymentStatus' => 'pending'
        ], ['id' => 'DESC']);

        if ($form->isSubmitted() && $form->isValid()) {

            $premiumAdvert = $form->getData();
            $premiumAdvert->setUserId($user->getId());
            $premiumAdvert->setCreationDate(new DateTime());
            $premiumAdvert->setCreationIpaddr($request->getClientIp());
            if (!$request->query->has('id')) {
                $premiumAdvert->setPaid(0);
            }
            $entityManager->persist($premiumAdvert);
            $entityManager->flush();

            if (($photosSession = $session->get('photos_premium_eventadvert', false)) != false) {
                foreach ($photosSession as $ph) {
                    if (($premiumEventadvertPhoto = $entityManager->getRepository(PremiumEventadvertPhoto::class)->findOneBy([
                            'temporaryId' => $ph['temporaryId']
                        ])) != null) {
                        $premiumEventadvertPhoto->setPremiumEventAdvert($premiumAdvert);
                        $entityManager->persist($premiumEventadvertPhoto);
                        $entityManager->flush();
                    }
                }
                $session->clear();
            }

            if ($premiumAdvert->getPaid() == 1) {
                return $this->redirectToRoute('dashboard_index_big_premium');
            } else {
                return $this->redirectToRoute('dashboard_premium_eventadvert_pay', ['id' => $premiumAdvert->getId()]);
            }

        } elseif ($form->isSubmitted()) {
            if (($photosSession = $session->get('photos_premium_eventadvert', false)) != false) {
                foreach ($photosSession as $ph) {
                    $premiumEventadvertPhotos[] = $entityManager->getRepository(PremiumEventadvertPhoto::class)->findOneBy([
                        'temporaryId' => $ph['temporaryId']
                    ]);
                }
            }
        }

        return $this->render('dashboard/eventadvert/premium.html.twig', [
            'form' => $form->createView(),
            'eventAdvertPremium' => $eventAdvertPremium,
            'premiumEventadvertPhotos' => $premiumEventadvertPhotos,
            'showFooter' => -1,
            'balance' => $user->getCredits(),
            'adverts' => $adverts
        ]);
    }

    #[Route(path: ['en' => '/dashboard/event/premium/photo', 'nl' => '/dashboard/evenement/premium/foto', 'fr' => '/dashboard/evenement/premium/photo'], name: 'dashboard_premium_eventadvert_photo_template')]
    public function premiumPhotoTemplate(
        Request                  $request, CompanyRepository $companyRepository, UserInterface $user, EventadvertRepository $EventadvertRepository
        , EntityManagerInterface $entityManager
    )
    {
        $photoReturn = false;

        if (($photo = $entityManager->getRepository(PremiumEventadvertPhoto::class)->findOneBy(['temporaryId' => $request->get('temp')])) && ($eventadvert = $entityManager->getRepository(EventadvertPremium::class)->findOneBy([
                'id' => $photo->getEventadvert()->getId(),
                'userId' => $user->getId()
            ]))) {
            $photoReturn = $photo;
        }

        return $this->render('dashboard/eventadvert/premium/photo.html.twig', [
            'photo' => $photoReturn,
            'balance' => $user->getCredits()
        ]);
    }

    /**
     * @return Response
     *
     */
    #[Route(path: ['en' => '/dashboard/big_premiums', 'nl' => '/dashboard/big_premiums', 'fr' => '/dashboard/big_premiums'], name: 'dashboard_index_big_premium')]
    public function index_big_premium(
        Request                      $request, UserInterface $user,
        EventadvertPremiumRepository $eventadvertPremiumRepository,
        CategoryRepository           $categoryRepository,
        ChannelRepository            $channelRepository
    )
    {
        $channels = $channelRepository->findAll();
        $categories = $categoryRepository->findAll();

        $linkEvents = $eventadvertPremiumRepository->findBy([
            'userId' => $user->getId(),
            'redirection_type' => 0
        ]);

        $qb = $eventadvertPremiumRepository->createQueryBuilder('eventadvert_premium');
        $qb->join(Eventadvert::class, 'event', Join::WITH, 'event.id = eventadvert_premium.redirection_link');

        $qb->andWhere('eventadvert_premium.redirection_type = 1');
        $qb->andWhere('eventadvert_premium.userId = :userId')->setParameter('userId', $user->getId());
        $qb->orderBy('eventadvert_premium.id', 'DESC');
        $eventEvents = $qb->getQuery()->getResult();

        $premiumEvents = array_merge($linkEvents, $eventEvents);
        usort($premiumEvents, function ($item1, $item2) {
            return $item1->getId() > $item2->getId();
        });

//        $premiumEvents = $eventadvertPremiumRepository->findBy([
//            'userId' => $user->getId()
//        ], ['id' => 'DESC']);

        return $this->render(
            'dashboard/index/big_premium.html.twig',
            [
                'events' => $premiumEvents,
                'channels' => $channels,
                'categories' => $categories,
                'ac' => $request->get('ac'),
                'balance' => $user->getCredits()
            ]
        );
    }

    #[Route(path: ['en' => '/dashboard/big-premium/delete', 'nl' => '/dashboard/big-premium/verwijder', 'fr' => '/dashboard/big-premium/supprimer'], name: 'dashboard_eventadvert_premium_delete')]
    public function remove_big_premium(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {
        if ($premiumEventAdvert = $entityManager->getRepository(EventadvertPremium::class)->findOneBy(['id' => $request->get('id'), 'userId' => $user->getId()])) {
            $entityManager->remove($premiumEventAdvert);
            $entityManager->flush();
        }

        return $this->redirectToRoute('dashboard_index_big_premium', ['ac' => 'del-advert', 'balance' => $user->getCredits()]);
    }

    #[Route(path: ['en' => '/dashboard/big-premium/move-photo', 'nl' => '/dashboard/big-premium/verplaats-foto', 'fr' => '/dashboard/big-premium/bouger-photo'], name: 'dashboard_premium_eventadvert_photos_move')]
    public function premiumEventPhotoMove(Request $request, EntityManagerInterface $entityManager)
    {
        $order = $request->get('order');

        foreach ($order as $key => $photoId) {
            if ($photo = $entityManager->getRepository(PremiumEventadvertPhoto::class)->find($photoId)) {
                $photo->setPriority(($key + 1));
                $entityManager->persist($photo);
                $entityManager->flush();
            }
        }

        return new JsonResponse(true);
    }

    #[Route(path: ['en' => '/dashboard/big-premium/delete-photo', 'nl' => '/dashboard/big-premium/verwijder-foto', 'fr' => '/dashboard/big-premium/supprimer-photo'], name: 'dashboard_premium_eventadvert_photos_delete')]
    public function premiumEventphotoDelete(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {
        if (($photo = $entityManager->getRepository(PremiumEventadvertPhoto::class)->findOneBy(['id' => $request->get('id')])) && $photo->getPremiumEventAdvert()->getUserId() == $user->getId()) {
            $photoId = $photo->getId();
            $entityManager->remove($photo);
            $entityManager->flush();
            return new JsonResponse([
                'success' => true,
                'id' => $photoId
            ]);
        }

        return new JsonResponse(false);
    }
}
