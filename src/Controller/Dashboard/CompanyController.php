<?php

namespace App\Controller\Dashboard;

use App\Entity\Company;
use App\Entity\CompanyPhoto;
use App\Entity\CompanyTag;
use App\Entity\Eventadvert;
use App\Entity\GeoPlaces;
use App\Entity\Tag;
use App\Entity\OpeningHour;
use App\Form\CompanyFormType;
use App\Repository\CompanyRepository;
use App\Repository\GeoPlacesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;


class CompanyController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/company/', 'nl' => '/dashboard/bedrijf/', 'fr' => '/dashboard/entreprise/'], name: 'dashboard_company')]
    public function index(Request $request, UserInterface $user, EntityManagerInterface $entityManager, GeoPlacesRepository $geoPlacesRepository)
    {
        $action = $request->get('action') ?? null;

        $opening_hours = null;
        $opening_hours_set = 0;

        if (($company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()])) == false) {
            $company = new Company();
        }

        $form = $this->createForm(CompanyFormType::class, $company);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $currentUser = $this->getUser();

            //$company = $form->getData();
            $company->setUser($currentUser);
            $company->setCreationDate(new DateTime());
            $company->setCreationIpaddr($request->getClientIp());
            $company->setStatus(1);
            $company->setTranslatableLocale($request->getLocale());

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
            if ($request->request->has('tags') && is_array($request->request->all('tags'))) {
                foreach ($request->request->all('tags') as $t) {
                    $t = trim($t);
                    $t = strip_tags($t);

                    if ((is_numeric($t) && $t > 0) || (is_string($t) && strlen($t) > 0)) {
                        $slugger = new AsciiSlugger();
                        $tagSlug = $slugger->slug($t);

                        $tagResult = $entityManager->getRepository(Tag::class)->findOneBy(['nameSlug' => $tagSlug]);

                        if ($tagResult == false) {
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
            $entityManager->flush();


            foreach ($request->request->all('openingHour') as $day => $o) {
                if(trim($o['from'][0]) === '' or trim($o['till'][0]) === ''){
                    continue;
                }

//                for ($x = 0; $x <= 1; $x++) {
                    $from = DateTime::createFromFormat('H:i', $o['from'][0]);
                    $till = DateTime::createFromFormat('H:i', $o['till'][0]);


                    if ($from !== false && $till !== false) {
                        $open = new OpeningHour();
                        $open->setCompany($company);
                        $open->setDay($day);
                        $open->setOpenFrom($from);
                        $open->setOpenTill($till);
                        $entityManager->persist($open);
                        $entityManager->flush();
                    }
//                }
            }

            /** @var CompanyPhoto $company_photos */
            $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC']);

            //return $this->redirectToRoute('dashboard_company_photos');
            /*return $this->render('dashboard/company/index.html.twig', [
                'form' => $form->createView(),
                'opening_hours' => $opening_hours,
                'tags' => $tags,
                'companyPhotos' => $company_photos
            ]);*/

        } elseif ($form->isSubmitted()) {
            $opening_hours_set = 1;
            $opening_hours = $request->request->all('openingHour');
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

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Eventadvert $events */
            $events = $entityManager->getRepository(Eventadvert::class)->findOneBy(['userId' => $user->getId()]);

            if (is_null($events)) {
                return $this->redirectToRoute('dashboard_event', ['cp' => 'companyprofile']);
            }
        }
        /** @var CompanyPhoto $company_photos */
        $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC']);
        return $this->render('dashboard/company/index.html.twig', [
            'form' => $form->createView(),
            'company' => $company,
            'opening_hours' => $opening_hours,
            'tags' => $tags,
            'companyPhotos' => $company_photos,
            'action' => $action,
            'coming_from' => $request->query->get('cf'),
            'balance' => $user->getCredits()
        ]);

    }

    #[Route(path: ['en' => '/dashboard/company/preview', 'nl' => '/dashboard/bedrijf/voorvertoning', 'fr' => '/dashboard/entreprise/avant-premiere'], name: 'dashboard_company_preview')]
    public function preview(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        /** @var Company $company */
        $company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()]);
        if (is_null($company)) {
            return $this->redirectToRoute('dashboard_company', ['cf' => 'berichten']);
        }


        $country = strtoupper($this->getParameter('country'));
        $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'id' => $company->getGeoPlacesId()
        ]);

        /** @var CompanyPhoto $company_photos */
        $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC']);
        /** @var OpeningHour $opening_hours */
        $opening_hours = $entityManager->getRepository(OpeningHour::class)->findBy(['company' => $company]);
        /** @var Tag $tags */
        $tags = $entityManager->getRepository(CompanyTag::class)->findBy(['company' => $company]);

        return $this->render('dashboard/company/preview.html.twig', [
            'city' => $geoCity,
            'company' => $company,
            'openingHours' => $opening_hours,
            'companyPhotos' => $company_photos,
            'tags' => $tags,
            'balance' => $user->getCredits()
        ]);
    }

    #[Route(path: ['en' => '/dashboard/company/photos', 'nl' => '/dashboard/bedrijf/fotos', 'fr' => '/dashboard/entreprise/photos'], name: 'dashboard_company_photos')]
    public function photos(EntityManagerInterface $entityManager, UserInterface $user)
    {

        $company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()]);
        $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC']);


        return $this->render('dashboard/company/photos.html.twig', [
            'companyPhotos' => $company_photos,
            'balance' => $user->getCredits()
        ]);
    }

    #[Route(path: ['en' => '/dashboard/company/delete-photo', 'nl' => '/dashboard/bedrijf/verwijder-foto', 'fr' => '/dashboard/entreprise/supprimer-photo'], name: 'dashboard_company_photos_delete')]
    public function photoDelete(Request $request, EntityManagerInterface $entityManager, UserInterface $user)
    {
        if ($photo = $entityManager->getRepository(CompanyPhoto::class)->findOneBy(['id' => $request->get('id')])) {
            $photoId = $photo->getId();
            $company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()]);
            if ($photo->getCompany()->getId() == $company->getId()) {
                $entityManager->remove($photo);
                $entityManager->flush();
                return new JsonResponse([
                    'success' => true,
                    'id' => $photoId
                ]);
            }
        }

        return new JsonResponse(false);
    }

    #[Route(path: ['en' => '/dashboard/company/move-photo', 'nl' => '/dashboard/bedrijf/verplaats-foto', 'fr' => '/dashboard/entreprise/bouger-photo'], name: 'dashboard_company_photos_move')]
    public function photoMove(Request $request, EntityManagerInterface $entityManager)
    {
        $order = $request->get('order');

        foreach ($order as $key => $photoId) {
            if ($photo = $entityManager->getRepository(CompanyPhoto::class)->find($photoId)) {
                $photo->setPriority(($key + 1));
                $entityManager->persist($photo);
                $entityManager->flush();
            }
        }

        return new JsonResponse(true);
    }

    #[Route(path: ['en' => '/dashboard/company/photo', 'nl' => '/dashboard/bedrijf/foto', 'fr' => '/dashboard/entreprise/photo'], name: 'dashboard_company_photo_template')]
    public function photoTemplate(Request $request, EntityManagerInterface $entityManager, UserInterface $user)
    {
        $photoReturn = false;
        if ($photo = $entityManager->getRepository(CompanyPhoto::class)->findOneBy(['temporaryId' => $request->get('temp')])) {
            $photoReturn = $photo;
            /*
            $company = $entityManager->getRepository(Company::class)->findOneBy(['userId' => $user->getId()]);
            if ($photo->getCompany()->getId() == $company->getId()) {
                $photoReturn = $photo;
            }
            */
        }

        return $this->render('dashboard/company/photo.html.twig', [
            'photo' => $photoReturn,
            'balance' => $user->getCredits()
        ]);

    }
}
