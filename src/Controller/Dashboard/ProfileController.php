<?php

namespace App\Controller\Dashboard;

use App\Entity\Company;
use App\Entity\User;
use App\Repository\CompanyRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Form\ProfileFormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;

class ProfileController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/profile/', 'nl' => '/dashboard/profiel/', 'fr' => '/dashboard/profile/'], name: 'dashboard_profile')]
    public function index(Request $request, UserInterface $user, UserPasswordHasherInterface $passwordEncoder, EntityManagerInterface $entityManager)
    {

        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $user->getId()]);

        $form = $this->createForm(ProfileFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('plainPassword')->getData() != "") { //If new password added to save
                $user->setPassword(
                    $passwordEncoder->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
                $this->addFlash(
                    'success',
                    'Indien u uw wachtwoord heeft veranderd kunt u vervolgens met het nieuwe wachtwoord inloggen.'
                );
            }

            if ($request->request->get('account_notification') !== null) {
                $user->setSendNotifications(1);
            } else {
                $user->setSendNotifications(0);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('dashboard_profile'); //To protect form re-submit on page refresh
        }

        return $this->render('dashboard/profile/index.html.twig', [
            'form' => $form->createView(),
            'balance' => $user->getCredits(),
            'user' => $user
        ]);
    }


    /**
     * Sends account deletion link by email to user then log him out.
     *
     * @throws AccessDeniedException|\Exception
     */
    #[Route(path: 'account/deletion-request', name: 'account_deletion_request', methods: 'POST')]
    public function redirectUser(Request $request, UserInterface $user, SessionInterface $session, Security $security, EntityManagerInterface $em): RedirectResponse
    {
        if ($this->isCsrfTokenValid('account_deletion_request', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        if ($user) {
            // force manual logout of logged in user
            $security->logout(false);

            $user->setDeleted(true);
            $user->setDeletedAt(new DateTime());
            $user->setApStatus(true);
            // $em->remove($user);
            $em->flush();

            $session->invalidate(0);

            return $this->redirectToRoute('home');

        } else {

            return $this->redirectToRoute('dashboard_profile');
        }
    }
}
