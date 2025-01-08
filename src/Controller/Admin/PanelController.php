<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Eventadvert;
use App\Entity\Company;
use App\Entity\CompanyTag;
use App\Entity\CompanyPhoto;
use App\Entity\OpeningHour;

use App\Entity\EventadvertPhoto;
use App\Entity\EventadvertTag;
use App\Entity\Tag;
use App\Entity\Invoice;
use App\Entity\InvoiceDetail;
use App\Entity\Transaction;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Form\UserAdminFormType;
use App\Form\CompanyFormType;
use App\Form\EventadvertFormType;
use App\Form\EventAdminFormType;

use App\Repository\CompanyRepository;
use App\Repository\GeoPlacesRepository;
use App\Repository\ChannelRepository;
use App\Repository\CategoryRepository;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query\Expr\Join;
class PanelController extends AbstractController
{

    #[Route(path: '/admin/panel/users', name: 'admin_panel_users')]
    public function users(Request $request, EntityManagerInterface $em)
    {
        $users = $em->getRepository(User::class)->findAll();

        if ($request->query->get('id') && $request->query->get('u') == 'restore') {
            $user = $em->getRepository(User::class)->find($request->query->get('id'));
            $user->setDeleted(0);
            $user->setDeletedAt(null);
            $user->setApStatus(false);
            $em->flush();
            return $this->redirectToRoute('admin_panel_users');
        }

        if ($request->query->get('id') && $request->query->get('u') == 'del') {
            $user = $em->getRepository(User::class)->find($request->query->get('id'));
            $user->setDeleted(true);
            $user->setDeletedAt(new DateTime());
            $user->setApStatus(true);
            $em->flush();
            return $this->redirectToRoute('admin_panel_users');
        }

        return $this->render('admin/panel/users.html.twig', ['users' => $users ]);
    }

    #[Route(path: '/admin/panel/users/{id}', name: 'admin_panel_users_edit')]
    public function editUserPanel(
        int $id,
        Request $request,
        GeoPlacesRepository $geoPlacesRepository,
        ChannelRepository $channelRepository,
        CategoryRepository    $categoryRepository,
        SessionInterface      $session,
        MailerInterface       $mailer,
        EntityManagerInterface $entityManager
    )
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
        $data = [];

        if ( $request->query->get('u') == 'b' && $request->query->get('ap') == 'b' ) {
            $user->setBlocked(true);
            $user->setApStatus(true);
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Account blocked')
                ->htmlTemplate('emails/html/account_blocked.html.twig');

            $mailer->send($email);

            return $this->redirectToRoute('admin_panel_users_edit', ['id' => $id ]);
        }

        if ( $request->query->get('u') == 'ub' && $request->query->get('ap') == 'ub' ) {
            $user->setBlocked(false);
            $user->setApStatus(false);
            $entityManager->flush();
            return $this->redirectToRoute('admin_panel_users_edit', ['id' => $id ]);
        }

        if ( $request->query->get('u') == 'ub' && $request->query->get('ap') == 'b' ) {
            $user->setBlocked(false);
            $user->setApStatus(true);
            $entityManager->flush();
            return $this->redirectToRoute('admin_panel_users_edit', ['id' => $id ]);
        }

        $formUser = $this->createForm(UserAdminFormType::class, $user);

        $formUser->handleRequest($request);

        if ($formUser->isSubmitted() && $formUser->isValid()) {

            $user->setEmail($formUser->get('email')->getData());
            $user->setFirstname($formUser->get('firstname')->getData());
            $user->setSurname($formUser->get('surname')->getData());
            $user->setRemarks($formUser->get('remarks')->getData());
            $user->setCredits($formUser->get('credits')->getData());

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('admin_panel_users_edit',  ['id' => $id]);
        }

        /** Advert */
        $editAdvert = false;
        $tabAdvertActive = false;
        $tabAdvertPremiumActive = false;
        $hasCompany = true;
        $adverts = $entityManager->getRepository(Eventadvert::class)->findBy(['userId' => $user->getId()]);
        $freeAdverts = 0;
        $paidAdverts = 0;
        $advertsInMonth = 0;
        $advertsInWeek = 0;
        foreach($adverts as $advert){
            if($advert->getStatus() == 1 and $advert->getPaymentStatus() === 'pending'){
                $freeAdverts += 1;
            }
            if($advert->getStatus() == 1 and $advert->getPaymentStatus() === 'paid'){
                $paidAdverts += 1;
            }
            $daysDiff = Carbon::parse($advert->getCreationDate())->diff('now', true)->get('day');
            if($daysDiff <= 7){
                $advertsInWeek += 1;
            }
            if($daysDiff <= 30){
                $advertsInMonth += 1;
            }

        }

        if ( $request->query->get('t') == 'ad' )
        {
            $tabAdvertActive = true;
        }

        if ( !$adverts || $request->query->get('ac') == 'edit' )
        {
            $eventadvertPhotos = [];
            $editAdvert = true;

            if (($eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy([
                'userId' => $user->getId(),
                'id' => $request->query->get('a_id')
            ]))) {
                $eventadvertPhotos = $entityManager->getRepository(EventadvertPhoto::class)->findBy([
                    'eventAdvert' => $request->query->get('a_id')
                ], ['priority' => 'ASC']);
            } else {
                $eventAdvert = new Eventadvert();
            }

            $companyAdvert = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()]);

            if (is_null($companyAdvert)) {
                $hasCompany = false;
            }

            $formAdvert = $this->createForm(EventadvertFormType::class, $eventAdvert);
            $formAdvert->handleRequest($request);

            if ($formAdvert->isSubmitted() && $formAdvert->isValid()) {
                /** @var Eventadvert $eventAdvert */
                $eventAdvert = $formAdvert->getData();
                $eventAdvert->setCompany($companyAdvert);
                $eventAdvert->setUserId($user->getId());
                $eventAdvert->setCreationDate(new DateTime());
                $eventAdvert->setCreationIpaddr($request->getClientIp());
                if ($request->request->get('all_day_event') !== null) {
                    $eventAdvert->setAllDayEvent(1);
                    $eventAdvert->setEventStartDate(null);
                    $eventAdvert->setEventEndDate(null);
                    $eventAdvert->setStartHour(null);
                    $eventAdvert->setEndHour(null);
                } else {
                    $eventAdvert->setAllDayEvent(0);
                }

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
                    if ($dataGeo = $geoPlacesRepository->findByIdAndLocale($eventAdvert->getGeoPlacesId(), $request->getLocale())) {
                        $enrichment .= $dataGeo[0]->getPostcode() . ' ' . $dataGeo[0]->getLocality() . ' ';
                    }
                } elseif ($companyAdvert->getGeoplacesId() !== null) {
                    if ($dataGeo = $geoPlacesRepository->findByIdAndLocale($companyAdvert->getGeoplacesId(), $request->getLocale())) {
                        $enrichment .= $dataGeo[0]->getPostcode() . ' ' . $dataGeo[0]->getLocality() . ' ';
                    }
                }

                $eventAdvert->setEnrichment($enrichment);
                if (empty($eventAdvert->getViews() )) {
                    $eventAdvert->setViews(0);
                }

                $entityManager->persist($eventAdvert);
                $entityManager->flush();

                $advertDescription = $eventAdvert->getDescription();
                $advertDescription = preg_replace('/<a href=.*?>/', '', $advertDescription);
                $advertDescription = preg_replace('/<\/a>/', '', $advertDescription);

                $urlPattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
                $advertDescription = preg_replace($urlPattern, '<a href="$1" target="_blank">$1</a>', $advertDescription);
                $advertDescription = preg_replace('/href="www/', 'href="http://www', $advertDescription);

                $mailPattern = "/[_A-Za-z0-9-]+(\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*(\.[A-Za-z]{2,3})/";
                $advertDescription = preg_replace($mailPattern, "<a href=\"mailto:\\0\">\\0</a>", $advertDescription);

                $eventAdvert->setDescription($advertDescription);
                $entityManager->flush();

                // save Tag
                if ($advertTags = $entityManager->getRepository(EventadvertTag::class)->findBy(['advert' => $eventAdvert])) {
                    foreach ($advertTags as $t) {
                        $entityManager->remove($t);
                        $entityManager->flush();
                    }
                }

                $advertTags = '';
                if ($request->request->get('tags') !== null && is_array($request->request->get('tags'))) {
                    foreach ($request->request->get('tags') as $t) {
                        $t = trim($t);
                        $t = strip_tags($t);

                        if ( (is_numeric($t) && $t > 0) || (is_string($t) && strlen($t) > 0) )
                        {
                            $slugger = new AsciiSlugger();
                            $tagSlug = $slugger->slug($t);

                            $tagResult = $entityManager->getRepository(Tag::class)->findOneBy(['nameSlug' => $tagSlug] );

                            if ( $tagResult == false )
                            {
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

                return $this->redirect($request->getRequestUri());
            } elseif ($formAdvert->isSubmitted()) {
                if (($photosSession = $session->get('photos_eventadvert', false)) != false) {
                    foreach ($photosSession as $ph) {
                        $eventadvertPhotos[] = $entityManager->getRepository(EventadvertPhoto::class)->findOneBy([
                            'temporaryId' => $ph['temporaryId']
                        ]);
                    }
                }
            }

            $advertTags = $entityManager->getRepository(EventadvertTag::class)->findBy(['advert' => $eventAdvert]);

            $data['formAdvert'] = $formAdvert->createView();
            $data['eventAdvert'] = $eventAdvert;
            $data['advertTags'] = $advertTags;
            $data['eventadvertPhotos'] = $eventadvertPhotos;
            $data['companyAdvert'] = $companyAdvert;

        }

        if ( $request->query->get('a_id') && $request->query->get('ac') == 'ps' )
        {
            $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->query->get('a_id')]);
            $eventAdvert->setPaused(true);
            $eventAdvert->setBlocked(false);
            $eventAdvert->setDeleted(false);
            $eventAdvert->setDeletedAt(null);
            $entityManager->flush();
        }

        if ( $request->query->get('a_id') && $request->query->get('ac') == 'ups' )
        {
            $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->query->get('a_id')]);
            $eventAdvert->setPaused(false);
            $entityManager->flush();
        }

        if ( $request->query->get('a_id') && $request->query->get('ac') == 'bl' )
        {
            $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->query->get('a_id')]);
            $eventAdvert->setBlocked(true);
            $eventAdvert->setPaused(false);
            $eventAdvert->setDeleted(false);
            $eventAdvert->setDeletedAt(null);
            $entityManager->flush();
        }

        if ( $request->query->get('a_id') && $request->query->get('ac') == 'ub' )
        {
            $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->query->get('a_id')]);
            $eventAdvert->setBlocked(false);
            $entityManager->flush();
        }

        if ( $request->query->get('a_id') && $request->query->get('ac') == 'del' )
        {
            $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->query->get('a_id')]);
            $eventAdvert->setDeleted(true);
            $eventAdvert->setDeletedAt(new DateTime());
            $eventAdvert->setPaused(false);
            $eventAdvert->setBlocked(false);
            $entityManager->flush();
        }

        if ( $request->query->get('a_id') && $request->query->get('ac') == 'restore' )
        {
            $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->query->get('a_id')]);
            $eventAdvert->setDeleted(false);
            $eventAdvert->setDeletedAt(null);
            $entityManager->flush();
        }

        /** End advert */


        /** Profile */

        $action = $request->get('action') ?? null;

        $opening_hours = null;
        $opening_hours_set = 0;
        $tabProfileActive = false;

        if (($company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()])) == false) {
            $company = new Company();
        }

        if ( $request->query->get('t') == 'pf' )
        {
            $tabProfileActive = true;
        }

        $formCompanyProfile = $this->createForm(CompanyFormType::class, $company);
        $formCompanyProfile->handleRequest($request);


        if ($formCompanyProfile->isSubmitted() && $formCompanyProfile->isValid()) {

            $company = $formCompanyProfile->getData();
            $company->setUserId($user->getId());
            $company->setCreationDate(new DateTime());
            $company->setCreationIpaddr($request->getClientIp());
            if ($request->request->get('only_appointment') !== null) {
                $company->setOnlyAppointment(1);
            } else {
                $company->setOnlyAppointment(0);
            }

            if ($request->request->get('webshop_only') !== null) {
                $company->setWebshopOnly(1);
            } else {
                $company->setWebshopOnly(0);
            }

            $entityManager->persist($company);
            $entityManager->flush();

            $companyDescription = $company->getDescription();
            $companyDescription = preg_replace('/<a href=.*?>/', '', $companyDescription);
            $companyDescription = preg_replace('/<\/a>/', '', $companyDescription);

            $urlPattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
            $companyDescription = preg_replace($urlPattern, '<a href="$1" target="_blank">$1</a>', $companyDescription);
            $companyDescription = preg_replace('/href="www/', 'href="http://www', $companyDescription);

            $mailPattern = "/[_A-Za-z0-9-]+(\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*(\.[A-Za-z]{2,3})/";
            $companyDescription = preg_replace($mailPattern, "<a href=\"mailto:\\0\">\\0</a>", $companyDescription);

            $company->setDescription($companyDescription);
            $entityManager->flush();


            // save Tag
            if ($tags = $entityManager->getRepository(CompanyTag::class)->findBy(['company' => $company])) {
                foreach ($tags as $t) {
                    $entityManager->remove($t);
                    $entityManager->flush();
                }
            }

            $tags = '';
            if ($request->request->get('tags') !== null && is_array($request->request->get('tags'))) {
                foreach ($request->request->get('tags') as $t) {
                    $t = trim($t);
                    $t = strip_tags($t);

                    if ( (is_numeric($t) && $t > 0) || (is_string($t) && strlen($t) > 0) )
                    {
                        $slugger = new AsciiSlugger();
                        $tagSlug = $slugger->slug($t);

                        $tagResult = $entityManager->getRepository(Tag::class)->findOneBy(['nameSlug' => $tagSlug] );

                        if ( $tagResult == false )
                        {
                            $tagResult = new Tag();

                            $repository = $entityManager->getRepository('Gedmo\Translatable\Entity\Translation');
                            $tagResult->setTranslatableLocale($request->getLocale());
                            $repository->translate($tagResult, 'name', $request->getLocale(), $t);

                            $tagResult->setName($t);
                            $entityManager->persist($tagResult);
                            $entityManager->flush();

                        }

                        $tag = new CompanyTag();
                        $tag->setCompany($company);
                        $tag->setTag($tagResult);

                        $entityManager->persist($tag);
                        $entityManager->flush();

                    }
                }
            }

            // save OpeningHour
            if ($openingHours = $entityManager->getRepository(OpeningHour::class)->findBy(['company' => $company->getId()])) {
                foreach ($openingHours as $o) {
                    $entityManager->remove($o);
                    $entityManager->flush();
                }
            }

            // enrichment
            $enrichment = '';

            // Enrich ZIP + City
            if ($company->getGeoplacesId() !== null && ($data = $geoPlacesRepository->findByIdAndLocale($company->getGeoplacesId(), $request->getLocale()))) {
                $enrichment .= $data[0]->getPostcode() . ' ' . $data[0]->getLocality() . ' ';
            }

            $company->setEnrichment($enrichment);


            foreach ($request->request->get('openingHour') as $day => $o) {
                for ($x = 0; $x <= 1; $x++) {
                    $from = DateTime::createFromFormat('H:i', $o['from'][$x]);
                    $till = DateTime::createFromFormat('H:i', $o['till'][$x]);


                    if ($from !== false && $till !== false) {
                        $open = new OpeningHour();
                        $open->setCompany($company);
                        $open->setDay($day);
                        $open->setOpenFrom($from);
                        $open->setOpenTill($till);
                        $entityManager->persist($open);
                        $entityManager->flush();
                    }
                }
            }

            /** @var CompanyPhoto $company_photos */
            $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC']);

            $editAdvert = false;
            $tabAdvertActive = false;

        } elseif ($formCompanyProfile->isSubmitted()) {
            $opening_hours_set = 1;
            $opening_hours = $request->request->get('openingHour');

            $editAdvert = false;
            $tabAdvertActive = false;

        }

        if ($opening_hours_set == 0) {
            $opening_hours = [];
            if ($open = $entityManager->getRepository(OpeningHour::class)->findBy(['company' => $company])) {
                foreach ($open as $oh) {
                    $from = $oh->getOpenFrom()->format('H:i');
                    $till = $oh->getOpenTill()->format('H:i');

                    $x = 0;

                    if (isset($opening_hours[$oh->getDay()]['from'][$x])) {
                        $x = 1;
                    }

                    $opening_hours[$oh->getDay()]['from'][$x] = $from;
                    $opening_hours[$oh->getDay()]['till'][$x] = $till;
                }
            }
        }

        $tags = $entityManager->getRepository(CompanyTag::class)->findBy(['company' => $company]);

        /** @var CompanyPhoto $company_photos */
        $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC']);

        /** End profile */

        /**  Premium advert */

        $editPremiumAdvert = false;
        if ( $request->query->get('t') == 'ad_p' )
        {
            $tabAdvertPremiumActive = true;
        }

        if ( $request->query->get('ap_id') && $request->query->get('ac') == 'edit' )
        {
            $editPremiumAdvert = true;
            $eventPremiumAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $request->query->get('ap_id')]);
            $formPremiumAdvert = $this->createForm(EventAdminFormType::class, $eventPremiumAdvert);
            $formPremiumAdvert->handleRequest($request);

            if ($formPremiumAdvert->isSubmitted() && $formPremiumAdvert->isValid()) {
                $eventPremiumAdvert = $formPremiumAdvert->getData();
                $entityManager->persist($eventPremiumAdvert);
                $entityManager->flush();
            }
            $data['eventPremiumAdvert'] = $eventPremiumAdvert;
            $data['formPremiumAdvert'] = $formPremiumAdvert->createView();
        }

        $premiumAdverts = $entityManager->getRepository(Eventadvert::class)->findBy([
            'userId' => $user->getId(),
            'paymentStatus' => 'paid'
        ]);

        /** End premium advert */

        /** Invoices  */
        $userInvoices = $entityManager->getRepository(Invoice::class)->findBy([
            'userId' => $user->getId()
        ]);
        /** End user invoices  */

        $transactions = [];

        // user transactions
        $baseQb = $entityManager->getRepository(Transaction::class)->createQueryBuilder('transaction');
        $baseQb->andWhere('transaction.user = :user');
        $baseQb->setParameter('user', $user);

        // transactions without eventadverts
        $nullTransactions = clone $baseQb;
        $nullTransactions->andWhere('transaction.eventAdvert IS NULL');
        $transactions = array_merge($transactions, $nullTransactions->getQuery()->getResult());

        // transactions with valid eventadverts
        $validTransactions = clone $baseQb;
        $validTransactions->innerJoin('transaction.eventAdvert', 'eventAdvert');
        $transactions = array_merge($transactions, $validTransactions->getQuery()->getResult());

        // transactions with invalid eventadverts
        $invalidQb = clone $baseQb;
//        $invalidQb->leftJoin('transaction.eventAdvert', 'eventAdvert');
        $invalidQb->andWhere('transaction.eventAdvert IS NOT NULL');
        $invalidQb->andWhere('transaction NOT IN (:previousTransactions)');
        $invalidQb->setParameter('previousTransactions', $transactions);
        $invalidTransactions = $invalidQb->getQuery()->getResult();
        /** @var Transaction $invalidTransaction */
        foreach($invalidTransactions as $invalidTransaction){
            try{
                $title = $invalidTransaction->getEventAdvert()->getTitle();
            }catch(EntityNotFoundException $exception){
                $eventAdvert = new Eventadvert();
                $eventAdvert->setTitle(sprintf('Event advert not found with ID %s', $invalidTransaction->getEventAdvert()->getId()));
                $invalidTransaction->setEventAdvert($eventAdvert);
            }
        }

        $transactions = array_merge_recursive($transactions, $invalidTransactions);

        $data['user'] = $user;
        $data['adverts'] = $adverts;
        $data['formUser'] = $formUser->createView();
        $data['formCompanyProfile'] = $formCompanyProfile->createView();
        $data['company'] = $company;
        $data['opening_hours'] = $opening_hours;
        $data['tags'] = $tags;
        $data['companyPhotos'] = $company_photos;
        $data['action'] = $action;
        $data['editAdvert'] = $editAdvert;
        $data['editPremiumAdvert'] = $editPremiumAdvert;
        $data['tabAdvertActive'] = $tabAdvertActive;
        $data['tabProfileActive'] = $tabProfileActive;
        $data['tabAdvertPremiumActive'] = $tabAdvertPremiumActive;
        $data['premiumAdverts'] = $premiumAdverts;
        $data['hasCompany'] = $hasCompany;
        $data['invoices'] = $userInvoices;
        $data['transactions'] = $transactions;
        $data['freeAdverts'] = $freeAdverts;
        $data['paidAdverts'] = $paidAdverts;
        $data['advertsInWeek'] = $advertsInWeek;
        $data['advertsInMonth'] = $advertsInMonth;

        if($request->query->has('allow-ads')){
            $user->setAllowUnlimitedFreeAdverts($request->query->get('allow-ads') == 1);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('admin/panel/detail.html.twig', $data);
    }

    #[Route(path: '/admin/panel/user-not-found', name: 'admin_panel_users_not_found')]
    public function getUserNotFoundPage()
    {
        return $this->render('admin/panel/not_found.html.twig');
    }


    #[Route(path: '/admin/panel/users/{user}/adverts/{advert}', name: 'admin_panel_advert_edit')]
    public function editAdvertPanel(
        int $user,
        int $advert,
        Request $request,
        ChannelRepository $channelRepository,
        GeoPlacesRepository $geoPlacesRepository,
        CategoryRepository    $categoryRepository,
        EntityManagerInterface $entityManager
    )
    {
        $eventAdvert = $entityManager->getRepository(Eventadvert::class)->findOneBy(['id' => $advert]);

        $eventadvertPhotos = [];

        $company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user]);

        if (is_null($company)) {
            return $this->redirectToRoute('dashboard_company', ['cf' => 'adverteren']);
        }

        $eventadvertPhotos = $entityManager->getRepository(EventadvertPhoto::class)->findBy([
            'eventAdvert' => $eventAdvert->getId()
        ], ['priority' => 'ASC']);


        $form = $this->createForm(EventadvertFormType::class, $eventAdvert);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Eventadvert $eventAdvert */
            $eventAdvert = $form->getData();

            $eventAdvert->setUserId($user->getId());
            $eventAdvert->setCompany($company);
            $eventAdvert->setCreationDate(new DateTime());

            if ($request->request->get('all_day_event') !== null) {
                $eventAdvert->setAllDayEvent(1);
                $eventAdvert->setEventStartDate(null);
                $eventAdvert->setEventEndDate(null);
                $eventAdvert->setStartHour(null);
                $eventAdvert->setEndHour(null);
            } else {
                $eventAdvert->setAllDayEvent(0);
            }


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
            if (empty($eventAdvert->getViews() )) {
                $eventAdvert->setViews(0);
            }

            $entityManager->persist($eventAdvert);
            $entityManager->flush();

            $advertDescription = $eventAdvert->getDescription();
            $advertDescription = preg_replace('/<a href=.*?>/', '', $advertDescription);
            $advertDescription = preg_replace('/<\/a>/', '', $advertDescription);

            $urlPattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
            $advertDescription = preg_replace($urlPattern, '<a href="$1" target="_blank">$1</a>', $advertDescription);
            $advertDescription = preg_replace('/href="www/', 'href="http://www', $advertDescription);

            $mailPattern = "/[_A-Za-z0-9-]+(\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*(\.[A-Za-z]{2,3})/";
            $advertDescription = preg_replace($mailPattern, "<a href=\"mailto:\\0\">\\0</a>", $advertDescription);

            $eventAdvert->setDescription($advertDescription);
            $entityManager->flush();

            // save Tag
            if ($tags = $entityManager->getRepository(EventadvertTag::class)->findBy(['advert' => $eventAdvert])) {
                foreach ($tags as $t) {
                    $entityManager->remove($t);
                    $entityManager->flush();
                }
            }

            $tags = '';
            if ($request->request->get('tags') !== null && is_array($request->request->get('tags'))) {
                foreach ($request->request->get('tags') as $t) {
                    $t = trim($t);
                    $t = strip_tags($t);

                    if ( (is_numeric($t) && $t > 0) || (is_string($t) && strlen($t) > 0) )
                    {
                        $slugger = new AsciiSlugger();
                        $tagSlug = $slugger->slug($t);

                        $tagResult = $entityManager->getRepository(Tag::class)->findOneBy(['nameSlug' => $tagSlug] );

                        if ( $tagResult == false )
                        {
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

            $session = $request->getSession();

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

            return $this->redirectToRoute('admin_panel_users_edit',  ['id' => $user->getId()]);
        } elseif ($form->isSubmitted()) {
            $session = $request->getSession();

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
            'ac' => $request->get('ac')
        ]);
    }


    public function displayAdvertPremiumPaidDate($advert, Request $request)
    {
        $form = $this->createForm(EventAdminFormType::class, $advert);
        $form->handleRequest($request);
        return $form;
    }

}
