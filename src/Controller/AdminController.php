<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route(path: '/admin/', name: 'admin_index')]
    public function index()
    {
        return $this->render('admin/index.html.twig', [
        ]);
    }

}
