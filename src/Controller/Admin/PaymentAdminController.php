<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class PaymentAdminController extends AbstractController
{

    #[Route(path: '/admin/payments', name: 'admin_payments')]
    public function indexPayments(Request $request, TransactionRepository $transactionRepository)
    {
        $allTransactions = $transactionRepository->getTransactions();

        $date_filter = null;
        if ($request->request->has('date_payment')) {
            $date_filter = $request->request->get('date_payment');
        }

        $advert_filter = null;
        if ($request->request->has('advert')) {
            $advert_filter = $request->request->get('advert');
        }

        $userPayments = '';
        $t = $transactionRepository->getTransactions($date_filter, $advert_filter);

        if ($t !== null) {
            $userPayments = $t;
        }
        $userDatesPayments = $transactionRepository->getDatesTransactions();

        $dates = [];
        $advertsPayments = [];

        foreach ($userDatesPayments as $datePayment) {
            $dates[$datePayment['dates']->format('Y-m-d')] = $datePayment['dates']->format('m/d/Y');
        }

        foreach ($allTransactions as $transaction) {
            $advertsPayments[$transaction->getEventAdvert()->getId()] = $transaction->getEventAdvert()->getTitle();
        }

        return $this->render('admin/payments/index.html.twig', [
            'payments' => $userPayments,
            'dates' => $dates,
            'events' => $advertsPayments,
            'date_filter' => $date_filter,
            'advert_filter' => $advert_filter
        ]);
    }

}
