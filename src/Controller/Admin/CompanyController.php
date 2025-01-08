<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Entity\CompanyPhoto;
use App\Entity\CompanyTag;
use App\Entity\Eventadvert;
use App\Entity\OpeningHour;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\UserVerification;
use App\Form\CompanyFormType;
use App\Form\UserVerificationType;
use App\Repository\CompanyRepository;
use App\Repository\GeoPlacesRepository;
use App\Repository\GeoRegionsRepository;
use App\Repository\UserVerificationRepository;
use App\Service\EmailService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/company')]
class CompanyController extends AbstractController
{
    #[Route('/', name: 'app_admin_company_index')]
    public function index(
        CompanyRepository $companyRepository, UserVerificationRepository $userVerificationRepository,
        GeoPlacesRepository $geoPlacesRepository, Request $request, GeoRegionsRepository $geoRegionsRepository
    ): Response
    {
        $locale = $request->getLocale();
        if ($locale == 'en') {
            $locale = 'nl';
        }

        $country = strtoupper($this->getParameter('country'));

        $companies = $companyRepository->createQueryBuilder('company')
            ->addSelect('user')
            ->join('company.user', 'user')
            ->andWhere('company.isClaimed = false')
            ->orderBy('company.id', 'DESC')
            ->getQuery()
            ->getArrayResult();

        foreach($companies as &$company){
            $claims = $userVerificationRepository->findBy(['company' => $company['id']]);

            $company['claims'] = $claims;

            $geoCity = $geoPlacesRepository->findOneBy([
                'iso' => $country,
                'language' => $locale,
                'id' => $company['geoPlacesId']
            ]);

            $geoProvince = $geoRegionsRepository->findOneBy([
                'iso' => $country,
                'language' => $locale,
                'iso2' => $geoCity->getIso2()
            ]);

            $company['province'] = $geoProvince->getNameDirify();
            $company['city'] = $geoCity->getLocalityDirify();
        }

        return $this->render('admin/company/index.html.twig', [
            'companies' => $companies,
        ]);
    }

    #[Route("/new", name: 'app_admin_company_new')]
    public function new(
        Request $request, EntityManagerInterface $entityManager, GeoPlacesRepository $geoPlacesRepository,
        UserPasswordHasherInterface $userPasswordHasher, ValidatorInterface $validator
    ){
        $action = $request->get('action') ?? null;

        $opening_hours = null;
        $opening_hours_set = 0;

        $company = new Company();
        $company->setClaimed(false);

        $form = $this->createForm(CompanyFormType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // create new user and attach it to the company
            $user = new User();
            $user->setEmail(sprintf('%s@%s.com', bin2hex(random_bytes(5)), bin2hex(random_bytes(3))));
            $user->setPassword($userPasswordHasher->hashPassword($user, bin2hex(random_bytes(8))));// set a random password here
            $user->setRoles(['ROLE_UNCLAIMED']);
            $user->setFirstname('');
            $user->setSurname('');
            $user->setEnabled(false);
            $user->setDeleted(false);
            $user->setRemarks('');
            $user->setBlocked(false);

            $violations = $validator->validate($user);
            if($violations->count() > 0){
                foreach($violations as $violation){
                    $this->addFlash('danger', $violation->getMessage());
                }
            }

            $entityManager->persist($user);

            //$company = $form->getData();
            $company->setUser($user);
            $company->setUserId($user->getId());
            $company->setCreationDate(new \DateTimeImmutable());
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

            return $this->redirectToRoute('app_admin_company_index');

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

        /** @var CompanyPhoto $company_photos */
        $company_photos = $entityManager->getRepository(CompanyPhoto::class)->findBy(['company' => $company], ['priority' => 'ASC']);
        return $this->render('admin/company/new.html.twig', [
            'form' => $form->createView(),
            'company' => $company,
            'opening_hours' => $opening_hours,
            'tags' => $tags,
            'companyPhotos' => $company_photos,
            'action' => $action,
            'coming_from' => $request->query->get('cf'),
        ]);
    }

    #[Route("/{companynameSlug}/claims", name: 'app_admin_company_claims')]
    public function claims(
        Company $company,  UserVerificationRepository $userVerificationRepository
    ){
        $claims = $userVerificationRepository->findBy(['company' => $company], ['createdAt' => 'desc']);

        return $this->render('admin/company/claims/claims.html.twig', [
            'claims' => $claims,
            'company' => $company
        ]);
    }

    #[Route("/{companynameSlug}/claims/{id}", name: 'app_admin_company_claim')]
    #[ParamConverter('company', options: ['mapping' => ['companynameSlug' => 'companynameSlug']])]
    #[ParamConverter('claim', options: ['mapping' => ['id' => 'id']])]
    public function claim(
        Company $company, UserVerification $claim,  UserVerificationRepository $userVerificationRepository
    ){
        return $this->render('admin/company/claims/claim.html.twig', [
            'claim' => $claim,
            'company' => $company
        ]);
    }

    #[Route("/{companynameSlug}/claims/{id}/approve", name: 'app_admin_company_claim_approve')]
    #[ParamConverter('company', options: ['mapping' => ['companynameSlug' => 'companynameSlug']])]
    #[ParamConverter('claim', options: ['mapping' => ['id' => 'id']])]
    public function approve(
        Company $company, UserVerification $claim,  UserVerificationRepository $userVerificationRepository,
        EntityManagerInterface $entityManager, LoginLinkHandlerInterface $loginLinkHandler, EmailService $emailService
    ){
        // delete other claims
        $claims = $userVerificationRepository->findBy(['company' => $company]);
        foreach($claims as $clm){
            if($clm === $claim){
                continue;
            }

            $entityManager->remove($clm);
        }

        // company deletion action
        if($claim->getCompanyAction() === UserVerificationType::COMPANY_ACTIONS[1]){
            // delete adverts
            $adverts = $entityManager->getRepository(Eventadvert::class)->findBy(['company' => $company]);
            foreach($adverts as $advert){
                $entityManager->remove($advert);
            }

            // remove user
            $user = $company->getUser();
            $entityManager->remove($user);

            // remove company
            $entityManager->remove($company);

            // remove claim as well
            $entityManager->remove($claim);
            $entityManager->flush();

            $this->addFlash('danger', 'Company, user, adverts and claim deleted.');

            return $this->redirectToRoute('app_admin_company_index');
        }

        // company ownership action
        if($claim->getCompanyAction() === UserVerificationType::COMPANY_ACTIONS[0]){
            // create user and attach with company
            $user = $company->getUser();
            $user->setEmail($claim->getEmail());
            $user->setFirstname($claim->getName());
            $user->setSurname($claim->getSurname());
            $user->setRoles(['ROLE_USER']);
            $user->setEnabled(true);

            $entityManager->persist($user);

            $company->setClaimed(true);
            $entityManager->persist($company);

            $entityManager->flush();

            // send magic link for login
            $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
            $loginLink = $loginLinkDetails->getUrl().'&first=1';

            // send email with link
            $emailService->claimSuccessEmail($user, $loginLink);

            $this->addFlash('success', 'Company claimed successfully');
            return $this->redirectToRoute('app_admin_company_index');
        }

        return $this->redirectToRoute('app_admin_company_claim', [
            'companynameSlug' => $company->getCompanynameSlug(),
            'id' => $claim->getId()
        ]);
    }

    #[Route("/{companynameSlug}/claims/{id}/reject", name: 'app_admin_company_claim_reject')]
    #[ParamConverter('company', options: ['mapping' => ['companynameSlug' => 'companynameSlug']])]
    #[ParamConverter('claim', options: ['mapping' => ['id' => 'id']])]
    public function reject(
        Company $company, UserVerification $claim,  UserVerificationRepository $userVerificationRepository,
        EmailService $emailService
    ){
        $this->addFlash('danger', 'Claim rejected with email for more information');
        $emailService->claimRejectEmail($claim);

        return $this->redirectToRoute('app_admin_company_claims', [
            'companynameSlug' => $company->getCompanynameSlug()
        ]);
    }
}
