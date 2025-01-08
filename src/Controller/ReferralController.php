<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReferralController extends AbstractController
{
    #[Route(path: '/ref/{token}', name: 'signup_referral')]
    public function checkReferral(
        $token, Request $request
    ){
        // store token in session
        $request->getSession()->set('referral_token', $token);

        // redirect to signup page now
        return $this->redirectToRoute('home_register');
    }
}