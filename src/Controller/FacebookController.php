<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FacebookController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process
     *
     *
     * @return RedirectResponse
     */
    #[Route(path: '/connect/facebook', name: 'connect_facebook_start')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('facebook')
            ->redirect([
                'public_profile', 'email'
            ]);
    }

    /**
     * After going to Facebook, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     *
     * @return RedirectResponse
     */
    #[Route(path: '/connect/facebook/check', name: 'connect_facebook_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        define('AVATAR_DIRECTORY', $this->getParameter('avatar_directory'));

        return $this->redirectToRoute('home');
    }
}