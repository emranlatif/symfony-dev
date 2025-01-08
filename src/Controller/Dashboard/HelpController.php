<?php

namespace App\Controller\Dashboard;

use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class HelpController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/help/', 'nl' => '/dashboard/help/', 'fr' => '/dashboard/help/'], name: 'dashboard_help')]
    public function index(Request $request, UserInterface $user, CompanyRepository $companyRepository)

    {
        return $this->render('dashboard/help/index.html.twig', ['balance' => $user->getCredits()]);

    }
}
