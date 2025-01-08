<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: ['en' => '/alogin/', 'nl' => '/alogin/', 'fr' => '/alogin/'], name: 'login')]
    public function ajaxLogin(AuthenticationUtils $authenticationUtils, Request $request)
    {
        if (!$request->isXMLHttpRequest() && !$_POST) {
            return $this->redirectToRoute('home');
        }

        if ($this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            return $this->redirectToRoute('dashboard_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $authenticationUtils->getLastUsername();

        $response = [];

        if ($error instanceof \Symfony\Component\Security\Core\Exception\AuthenticationException) {
            $response['security'] = $error->getMessageKey();
        }

        $response['warning'] = $request->getSession()->getFlashBag()->get("warning");

        return $this->json($response);
    }

    #[Route(path: ['en' => '/logout/', 'nl' => '/uitloggen/', 'fr' => '/deconnexion/'], name: 'logout')]
    public function logout()
    {
        // throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
