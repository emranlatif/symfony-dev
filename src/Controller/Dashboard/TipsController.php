<?php

namespace App\Controller\Dashboard;

use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class TipsController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/tips/', 'nl' => '/dashboard/tips/', 'fr' => '/dashboard/tips/'], name: 'dashboard_tips')]
    public function index(Request $request, UserInterface $user, CompanyRepository $companyRepository)

    {
        return $this->render('dashboard/tips/index.html.twig', ['balance' => $user->getCredits()]);

    }
}
