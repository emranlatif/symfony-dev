<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessHandlerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator
    )
    {

    }

    public static function getSubscribedEvents(){
        return [
            LoginSuccessEvent::class => 'onLoginSuccess'
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        /** @var User $user */
        $user = $event->getUser();

        $user->setLastLogin(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $request = $event->getRequest();
        if($request->query->has('first')){
            $request->getSession()->getFlashBag()->add('notice', 'Please change your password first');

            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('dashboard_profile')));
        }
    }
}
