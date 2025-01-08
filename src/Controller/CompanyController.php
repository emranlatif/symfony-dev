<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\CompanyTag;
use App\Entity\GeoRegions;
use App\Entity\GeoPlaces;

use App\Entity\Message;
use App\Entity\OpeningHour;
use App\Entity\CompanyPhoto;
use App\Entity\User;

use App\Entity\UserVerification;
use App\Form\CompanyFormType;
use App\Form\MessageFormType;
use App\Form\UserVerificationType;
use App\Repository\ReviewRepository;
use App\Service\UploadService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Repository\EventadvertRepository;
use App\Repository\GeoPlacesRepository;
use App\Repository\ChannelRepository;
use App\Repository\CategoryRepository;

class CompanyController extends AbstractController
{
    #[Route(path: ['en' => '/companies/', 'nl' => '/bedrijven/', 'fr' => '/societes/'], name: 'company_index')]
    public function index(Request $request)
    {
        return $this->render('company/index.html.twig', []);
    }

    #[Route(path: ['en' => '/companies/claim/{companynameSlug}', 'nl' => '/bedrijven/claim/{companynameSlug}', 'fr' => '/societes/claim/{companynameSlug}'], name: 'claim_company')]
    public function claim(
        Request $request,
        Company $companyData,
        EntityManagerInterface $entityManager,
        UploadService $uploadService
    ): Response {
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $companyData->getUserId()]);

        if ($user->getBlocked() == true) {
            header('Refresh: 2; url=/');
            return $this->json(['Deze advertentie is niet meer beschikbaar']);
        }

        $userVerification = new UserVerification();

        if ($request->query->has('hash')) {
            $userVerification = $entityManager->getRepository(UserVerification::class)->findOneBy([
                'verificationToken' => $request->query->get('hash')
            ]);
        }

        $userVerification->setCreatedAt(new \DateTimeImmutable());
        $userVerification->setCompany($companyData);
        $userVerification->setVerificationToken(bin2hex(random_bytes(32)));

        $form = $this->createForm(UserVerificationType::class, $userVerification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // validate for duplicate from users table email
            /** @var User $user */
            $user = $entityManager->getRepository(User::class)->createQueryBuilder('user')
                ->where('user.email = :email')
                ->setParameter('email', $userVerification->getEmail())
                ->leftJoin(Company::class, 'company', Join::WITH, 'company.user = user')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            if ($user !== null) {
                $form->get('email')->addError(new FormError('Duplicate email address'));
                if ($form->getErrors()->count() > 0) {
                    return $this->render('company/claim.html.twig', [
                        'form' => $form->createView(),
                        'company' => $companyData,
                    ]);
                }
            }

            $file = $uploadService->upload($form->get('proofOfOwnership')->getData());

            $userVerification->setProofOfOwnership($file);

            $entityManager->persist($userVerification);
            $entityManager->flush();

            $this->addFlash('alert-success', 'Your claim has been filed. An automated email will be sent to you about the process.');

            return $this->redirectToRoute('claim_company', [
                'companynameSlug' => $companyData->getCompanynameSlug(),
            ]);
        }

        return $this->render('company/claim.html.twig', [
            'form' => $form->createView(),
            'company' => $companyData,
        ]);
    }

    #[Route(path: ['en' => '/companies/{province}/', 'nl' => '/bedrijven/{province}/', 'fr' => '/societes/{province}/'], name: 'company_province')]
    public function province(
        Request $request,
        string $province,
        EntityManagerInterface $entityManager
    ) {
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $geoProvince = $entityManager->getRepository(GeoRegions::class)->findOneBy([
            'iso' => $country,
            'level' => 2,
            'language' => $locale,
            'nameDirify' => $province
        ], ['name' => 'ASC']);

        $cities = $entityManager->getRepository(GeoPlaces::class)->findActiveCities($locale, $country, $geoProvince->getIso2());


        return $this->render('company/province.html.twig', [
            'province' => $geoProvince,
            'cities' => $cities,
        ]);
    }

    #[Route(path: ['en' => '/companies/{province}/{city}/', 'nl' => '/bedrijven/{province}/{city}/', 'fr' => '/societes/{province}/{city}/'], name: 'company_city')]
    public function city(Request $request, string $province, string $city, EntityManagerInterface $entityManager)
    {
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }


        $country = strtoupper($this->getParameter('country'));

        $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'localityDirify' => $city
        ]);

        substr($geoCity->getPostcode(), 0, -3);

        $geoProvince = $entityManager->getRepository(GeoRegions::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'iso2' => $geoCity->getIso2()
        ]);


        // $companies = $entityManager->getRepository(Company::class)->findBy([
        //     'geoPlacesId' => $geoCity->getId(),
        // ]);

        // $companies = $entityManager->getRepository(Company::class)->findByRefPostCode($refPostcodeCity);
        $companies = $entityManager->getRepository(Company::class)
            ->createQueryBuilder('c')  // 'c' is an alias for Company
            ->innerJoin('c.user', 'u')  // Join the 'user' relation from Company to User
            ->where('c.geoPlacesId = :geoCityId') // Filter by geoPlaceId
            ->andWhere('u.deleted = 0') // Ensure the user is not deleted
            ->andWhere('u.blocked = 0') // Ensure the user is not blocked
            ->andWhere('u.enabled = 1') // Ensure the user is enabled
            ->setParameter('geoCityId', $geoCity->getId()) // Set geoCityId parameter
            ->getQuery()  // Execute the query
            ->getResult(); // Get the result as an array of Company entities


        return $this->render('company/city.html.twig', [
            'city' => $geoCity,
            'province' => $geoProvince,
            'companies' => $companies
        ]);
    }

    #[Route(path: ['en' => '/companies/{province}/{city}/{company}.html', 'nl' => '/bedrijven/{province}/{city}/{company}.html', 'fr' => '/societes/{province}/{city}/{company}.html'], name: 'company_company')]
    public function company(
        Request $request,
        string $province,
        string $city,
        string $company,
        EventadvertRepository $eventadvertRepository,
        GeoPlacesRepository $geoPlacesRepository,
        ChannelRepository $channelRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        ReviewRepository $reviewRepository
    ) {
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'localityDirify' => $city
        ]);

        $geoProvince = $entityManager->getRepository(GeoRegions::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'iso2' => $geoCity->getIso2()
        ]);

        $companyData = $entityManager->getRepository(Company::class)->findOneBy([
            'companynameSlug' => $company,
            // 'geoPlacesId' => $geoCity->getId(),
        ]);

        $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $companyData], ['priority' => 'ASC']);
        $opening_hours = $entityManager->getRepository(OpeningHour::class)->findBy(['company' => $companyData]);
        $tags = $entityManager->getRepository(CompanyTag::class)->findBy(['company' => $companyData]);
        $activeAdverts = $eventadvertRepository->getCompanyAllActiveAdverts($companyData);
        $geoPlaces = $geoPlacesRepository->getByEvents($activeAdverts, $request->getLocale());
        $channels = $channelRepository->findAll();
        $categories = $categoryRepository->findAll();

        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $companyData->getUserId()]);

        if ($user->getBlocked() == true) {
            header('Refresh: 2; url=/');
            return $this->json(['Deze advertentie is niet meer beschikbaar']);
        }

        $reviews = $reviewRepository->findApproved($companyData->getId());

        $totalStars = 0;
        $starsCount = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];
        foreach ($reviews as $review) {
            $totalStars += $review->getStars();

            $starsCount[$review->getStars()] += 1;
        }

        return $this->render('company/company.html.twig', [
            'city' => $geoCity,
            'province' => $geoProvince,
            'company' => $companyData,
            'openingHours' => $opening_hours,
            'companyPhotos' => $company_photos,
            'tags' => $tags,
            'adverts' => $activeAdverts,
            'geoPlaces' => $geoPlaces,
            'channels' => $channels,
            'categories' => $categories,
            'reviews' => $reviews,
            'rating' => count($reviews) === 0 ? 0 : $totalStars / count($reviews),
            'totalStars' => $totalStars,
            'starsCount' => $starsCount
        ]);
    }

    #[Route(path: ['en' => '/companies/contact/{province}/{city}/{company}.html', 'nl' => '/bedrijven/contact/{province}/{city}/{company}.html', 'fr' => '/societes/contact/{province}/{city}/{company}.html'], name: 'contact_company')]
    public function contact(Request $request, string $province, string $city, string $company, EntityManagerInterface $entityManager)
    {
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'id' => $city
        ]);

        $geoProvince = $entityManager->getRepository(GeoRegions::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'iso2' => $geoCity?->getIso2()
        ]);

        $companyData = $entityManager->getRepository(Company::class)->findOneBy([
            'companynameSlug' => $company,
            'geoPlacesId' => $geoCity?->getId(),
        ]);

        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $companyData->getUserId()]);

        if ($user->getBlocked() == true) {
            header('Refresh: 2; url=/');
            return $this->json(['Deze advertentie is niet meer beschikbaar']);
        }

        $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $companyData], ['priority' => 'ASC']);
        $opening_hours = $entityManager->getRepository(OpeningHour::class)->findBy(['company' => $companyData]);
        $tags = $entityManager->getRepository(CompanyTag::class)->findBy(['company' => $companyData]);

        $message = new Message();

        $form = $this->createForm(MessageFormType::class, $message);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Message $message */
            $message = $form->getData();

            $received = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

            $message->setReceived($received);
            $message->setIsRead(0);
            $message->setCompanyId($companyData->getId());

            $entityManager->persist($message);
            $entityManager->flush();

            //dd($message);

            $this->addFlash('form-send', 'message_send');
        }

        return $this->render('company/contact.html.twig', [
            'form' => $form->createView(),
            'city' => $geoCity,
            'province' => $geoProvince,
            'company' => $companyData,
            'openingHours' => $opening_hours,
            'companyPhotos' => $company_photos,
            'tags' => $tags
        ]);
    }

    #[Route(path: ['en' => '/getCompanyUrlByGeoPlacesID', 'nl' => '/getCompanyUrlByGeoPlacesID', 'fr' => '/getCompanyUrlByGeoPlacesID'], name: 'getCompanyUrlByGeoPlacesID')]
    public function getCompanyUrlByGeoPlacesID(Request $request, $geoID, $company, EntityManagerInterface $entityManager)
    {
        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
            'id' => $geoID
        ]);

        $geoProvince = $entityManager->getRepository(GeoRegions::class)->findOneBy([
            'iso' => $country,
            'language' => $locale,
            'iso2' => $geoCity->getIso2()
        ]);

        $companyData = $entityManager->getRepository(Company::class)->findOneBy([
            'companynameSlug' => $company
        ]);


        return $this->render('/company_url.html.twig', [
            'city' => $geoCity,
            'province' => $geoProvince,
            'company' => $companyData
        ]);
    }
}
