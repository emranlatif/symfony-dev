<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Entity\Eventadvert;
use App\Entity\EventadvertPhoto;
use App\Entity\EventadvertTag;
use App\Entity\Tag;
use App\Form\EventadvertFormType;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use App\Repository\EventadvertRepository;
use App\Repository\GeoPlacesRepository;
use Asika\Autolink\Autolink;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/admin/company/{companynameSlug}/adverts', name: 'app_admin_company_advert_')]
class CompanyAdvertController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function index(
        Company $company, EventadvertRepository $eventadvertRepository, CategoryRepository $categoryRepository
    ): Response
    {
        $adverts = $eventadvertRepository->findBy(['company' => $company]);
        $categories = $categoryRepository->findAll();

        return $this->render('admin/company/adverts/index.html.twig', [
            'adverts' => $adverts,
            'company' => $company,
            'categories' => $categories,
        ]);
    }

    #[Route("/new", name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, Company $company,
        GeoPlacesRepository      $geoPlacesRepository,
        ChannelRepository        $channelRepository,
        CategoryRepository       $categoryRepository,
        SessionInterface         $session
        , EntityManagerInterface $entityManager
    ){

        $eventAdvert = new Eventadvert();
        $eventAdvert->setCompany($company);

        $eventadvertPhotos = [];

        $form = $this->createForm(EventadvertFormType::class, $eventAdvert, [
            'startDate' => $eventAdvert->getEventStartDate(),
            'endDate' => $eventAdvert->getEventEndDate(),
        ]);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Eventadvert $eventAdvert */
            $eventAdvert = $form->getData();

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

            $eventAdvert->setUserId($company->getUser()->getId());
            $eventAdvert->setCompany($company);
            $eventAdvert->setCreationDate(new DateTime());
            $eventAdvert->setCreationIpaddr($request->getClientIp());
            $eventAdvert->setStatus(1);
            $eventAdvert->setPaidDate(new \DateTime());
            $eventAdvert->setPaymentStatus('paid');

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

            return $this->redirectToRoute('app_admin_company_advert_list', ['companynameSlug' => $company->getCompanynameSlug()]);
//            return $this->redirectToRoute('dashboard_eventadvert_upsale', ['id' => $eventAdvert->getId()]);
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


        return $this->render('admin/company/adverts/new.html.twig', [
            'form' => $form->createView(),
            'eventAdvert' => $eventAdvert,
            'company' => $company,
            'tags' => $tags,
            'eventadvertPhotos' => $eventadvertPhotos,
            'ac' => $request->get('ac'),
            'showFooter' => -1,
            'companyprofile' => $request->query->get('cp')
        ]);
    }
}
