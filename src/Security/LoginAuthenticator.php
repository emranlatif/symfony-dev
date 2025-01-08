<?php

namespace App\Security;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use function intval;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private EntityManagerInterface    $entityManager,
        private UrlGeneratorInterface     $urlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager
    )
    {
    }

    // Authenticate the user
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        // Log the session username
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        
        // Retrieve user from database
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Log the result of the user lookup
        if ($user) {
            if (intval($user->getEnabled()) !== 1) {
                throw new CustomUserMessageAuthenticationException('Account not activated.');
            }
    
            if (intval($user->getDeleted()) === 1) {
                throw new CustomUserMessageAuthenticationException('Account deleted.');
            }
    
            if (intval($user->getBlocked()) === 1) {
                throw new CustomUserMessageAuthenticationException('Your account is suspended.');
            }
        } 

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }


        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }

        if (intval($user->getEnabled()) !== 1) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Account not activated.');
        }

        if (intval($user->getDeleted()) === 1) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Account deleted.');
        }

        if (intval($user->getBlocked()) === 1) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Your account is suspended.');
        }

        $user->setLastLogin(new DateTime());
        $this->entityManager->flush();

        return $user;
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('login');
    }
}
