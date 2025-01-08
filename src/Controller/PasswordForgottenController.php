<?php

namespace App\Controller;

use App\Entity\PasswordForgotten;
use App\Entity\User;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;


class PasswordForgottenController extends AbstractController
{
    #[Route(path: ['en' => '/password-forgotten/', 'nl' => '/paswoord-vergeten/', 'fr' => '/mot-de-passe-oublie/'], name: 'password_forgotten')]
    public function index(Request $request, TranslatorInterface $translator, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        //  pwd forgotten form
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(), new Email()
                ]
            ])
            ->add('save', SubmitType::class, ['label' => 'Send'])
            ->getForm();

        // process form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // get repositories & entitymanager
            $repository_user = $entityManager->getRepository(User::class);
            $repository_pwd = $entityManager->getRepository(PasswordForgotten::class);

            // check user
            if ($user = $repository_user->findOneBy(['email' => $data['email']])) {
                $hash = $this->generateHash();

                if ($pwd_entry = $repository_pwd->findOneBy(['userId' => $user->getId()])) {
                    $entityManager->remove($pwd_entry);
                }

                // save pwd forgotten hash
                $pwf = new PasswordForgotten();
                $pwf->setUserId($user->getId());
                $pwf->setHash($hash);
                $pwf->setRequestDate(new DateTime("now"));
                $pwf->setRequestIpaddr($request->getClientIp());

                $entityManager->persist($pwf);
                $entityManager->flush();

                // send e-mail to user
                $email = (new TemplatedEmail())
                    ->from(new Address($this->getParameter('mail.info.email'), $this->getParameter('mail.info.name')))
                    ->to($user->getEmail())
                    ->subject('Wachtwoord vergeten')
                    ->htmlTemplate('emails/html/password_forgotten.html.twig')

                    // pass variables (name => value) to the template
                    ->context([
                        'firstname' => $user->getFirstname(),
                        'hash' => $hash,
                    ]);

                $mailer->send($email);
            }

            // show sent message
            $this->addFlash(
                'success',
                $translator->trans('An e-mail has been sent to {email}. Please check your e-mail to set a new password.', ['{email}' => $data['email']])
            );
        }

        return $this->render('password_forgotten/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route(path: ['en' => '/password-reset/', 'nl' => '/paswoord-instellen/', 'fr' => '/nouveau-mot-de-passe/'], name: 'password_reset')]
    public function setPassword(Request $request, TranslatorInterface $translator, UserPasswordHasherInterface $passwordEncoder, EntityManagerInterface $entityManager)
    {

        $repository_user = $entityManager->getRepository(User::class);
        $repository_pwd = $entityManager->getRepository(PasswordForgotten::class);

        // check hash
        if ($pwd = $repository_pwd->findOneByHash($request->query->get('h'))) {
            //  pwd reset form
            $form = $this->createFormBuilder()
                ->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter a password',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            'max' => 4096,
                        ]),
                    ],
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => true,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Repeat Password'],
                ])
                ->add('save', SubmitType::class, ['label' => 'Set Password'])
                ->getForm();


            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $user = $repository_user->find($pwd->getUserId());
                $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $entityManager->persist($user);
                $entityManager->flush();

                $entityManager->remove($pwd);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    $translator->trans('New password set, login now')
                );

                return $this->redirectToRoute('login');
            }
        } else {
            $this->addFlash(
                'error',
                $translator->trans('Error password reset, max 4 hours')
            );
            return $this->redirectToRoute('password_forgotten');
        }

        return $this->render('password_forgotten/reset.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function generateHash(int $length = 72)
    {
        $length = max(4, $length);
        return bin2hex(random_bytes(($length - ($length % 2)) / 2));
    }
}
