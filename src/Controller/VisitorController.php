<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Visitor;
use App\Repository\CompanyRepository;
use App\Repository\VisitorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class VisitorController extends AbstractController
{
    #[Route(path: ['en' => '/vAuth/', 'nl' => '/vAuth/', 'fr' => '/vAuth/'], name: 'ajax_vAuth')]
    public function vAuth(Request $request, VisitorRepository $repository, SessionInterface $session, EntityManagerInterface $entityManager)
    {
        // check if email already exists
        $visitor = $repository->findOneBy(['email' => $request->get('email')]);

        if (!$visitor) {
            if(!$request->get('name') || !$request->get('email') || !$request->get('password'))
            {
                return $this->json(['error' => 'Please fill all required fields']);
            }
            $visitor = new Visitor();
            $visitor->setName($request->get('name'));
            $visitor->setEmail($request->get('email'));
            $visitor->setPassword(hash('sha256', $request->get('password')));

            $entityManager->persist($visitor);
            $entityManager->flush();
        }

        $session->set('visitor', $visitor);

        return $this->json(['success' => true]);
    }

    #[Route(path: ['en' => '/vReview/', 'nl' => '/vReview/', 'fr' => '/vReview/'], name: 'submit_vReview')]
    public function vReview(Request $request, VisitorRepository $repository, SessionInterface $session, CompanyRepository $companyRepository, EntityManagerInterface $entityManager)
    {
        // check if email already exists
        $visitor = $repository->findOneBy(['email' => $session->get('visitor')->getEmail()]);

        if (!$visitor)
        {
            $session->remove('visitor');
            return $this->redirectToRoute('home');
        }

        $company = $companyRepository->find($request->get('company'));
        $review = new Review();
        $review->setStars($request->get('rating'));
        $review->setMessage($request->get('message'));
        $review->setCompany($company);
        $review->setVisitor($visitor);
        $review->setApproved(0);

        $entityManager->persist($review);
        $entityManager->flush();

        $route = $request->headers->get('referer');

        return $this->redirect($route);

    }
}
