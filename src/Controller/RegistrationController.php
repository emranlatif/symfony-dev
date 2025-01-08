<?php

namespace App\Controller;

use App\Entity\Referral;
use App\Entity\Referred;
use App\Entity\Reward;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\LoginAuthenticator;
use App\Service\RewardService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private $entityManager;

    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route(path: ['en' => '/aregister/', 'nl' => '/aregister/', 'fr' => '/aregister/'], name: 'ajax_register')]
    public function register(
        Request $request, UserPasswordHasherInterface $passwordEncoder, MailerInterface $mailer
        , EntityManagerInterface $entityManager, TransportInterface $transport
    ): Response
    {

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $this->generateHash(68);
            // $user->setEnabled(true);
            $user->setEnabled(0);
            $user->setDeleted(0);
            $user->setValidationHash($hash);
            $user->setPassword(
                $passwordEncoder->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();
            // check for referral link
            if($request->getSession()->has('referral_token')){
                // add in database and give reward later
                $this->processReferral($request->getSession()->get('referral_token'), $user, $request);
            }
            // do anything else you need here, like send an email
            $email = (new TemplatedEmail())
                ->from(new Address($this->getParameter('mail.info.email'), $this->getParameter('mail.info.name')))
                ->to($user->getEmail())
                ->subject('Welkom bij Promotip!')
                ->htmlTemplate('emails/html/register.html.twig')

                // pass variables (name => value) to the template
                ->context([
                    'firstname' => $user->getFirstname(),
                    'hash' => $hash
                ]);
//            $mailer->send($email);
            $transport->send($email)->getDebug();
            /*
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
            */
            return $this->redirectToRoute('register_done');
        } elseif ($form->isSubmitted()) {
            $errors = $this->getErrorMessages($form);
            return $this->json(['errors' => $errors]);
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function getErrorMessages(Form $form)
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    #[Route(path: ['en' => '/register/done', 'nl' => '/registreren/klaar', 'fr' => '/inscription/fini'], name: 'register_done')]
    public function registerDone(): Response
    {
        return $this->render('registration/done.html.twig', [
        ]);
    }

    #[Route(path: ['en' => '/register/activate', 'nl' => '/registreren/activeren', 'fr' => '/inscription/activation'], name: 'register_activate')]
    public function registerActivate(
        Request $request, UserRepository $userRepository, Security $security,
        LoginAuthenticator $authenticator, RewardService $rewardService
    ): Response
    {
        if ($user = $userRepository->findOneBy(['validationHash' => $request->query->get('h')])) {
            $user->setEnabled(true);
            $user->setValidationHash('');
            $this->entityManager->flush();

            $security->login($user, LoginAuthenticator::class, 'main');

            // validate and reward with referral program
            $referred = $this->entityManager->getRepository(Referred::class)->findOneBy([
                'childUser' => $user
            ]);
            if($referred !== null){
                $rewardService->checkSignup($referred);
            }

            return $this->redirectToRoute('dashboard_company', ['action' => 'register']);

        } else {
        }


        return $this->render('registration/activate.html.twig', [
        ]);
    }

    private function processReferral(string $token, User $user, Request $request): void {
        $link = $this->entityManager->getRepository(Referral::class)->findOneBy([
            'link' => $token
        ]);

        if($link === null){
            $this->logger->notice(sprintf('Invalid link found %s', $token));
            return;
        }

        $referred = new Referred();
        $referred->setParentUser($link->getUser());
        $referred->setChildUser($user);
        $referred->setCreatedAt(new \DateTime());
        $referred->setHttpReferrer($request->server->get('HTTP_REFERER'));
        $referred->setIpAddress($request->getClientIp());
        $referred->setIsProcessed(false);

        $this->entityManager->persist($referred);
        $this->entityManager->flush();
    }

    private function generateHash(int $length = 72)
    {
        $length = max(4, $length);
        return bin2hex(random_bytes(($length - ($length % 2)) / 2));
    }
}
