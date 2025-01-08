<?php

namespace App\Controller\Dashboard;

use App\Entity\GeoPlaces;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManager;

use App\Entity\User;
use App\Entity\UserCredit;
use App\Entity\Transaction;
use App\Entity\Invoice;
use App\Entity\InvoiceDetail;
use App\Repository\CompanyRepository;
use App\Repository\InvoiceRepository;
use App\Service\EmailService;
use Symfony\Component\Mailer\MailerInterface;

class CreditController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    #[Route(path: ['en' => '/dashboard/credit/', 'nl' => '/dashboard/credit/', 'fr' => '/dashboard/credit/'], name: 'dashboard_credit')]
    public function index(UserInterface $user, EntityManagerInterface $entityManager)
    {
        $creditsPackages = [ 10, 20, 25, 30, 50 ];
        $creditUnit = $this->getParameter('credit_amount');

        // if ($userCredit = $entityManager->getRepository(UserCredit::class)->findOneBy(['userId' => $user->getId()])) {
        //     $balance = $balance + $userCredit->getCreditBalance();
        // }

        return $this->render('dashboard/credit/index.html.twig', [
            'credits' => $creditsPackages,
            'creditUnit' => $creditUnit,
            'balance' => $user->getCredits()
        ]);
    }

    #[Route(path: ['en' => '/dashboard/credit/store', 'nl' => '/dashboard/credit/store', 'fr' => '/dashboard/credit/store'], name: 'dashboard_store_credit')]
    public function store(Request $request, UserInterface $user, EntityManagerInterface $entityManager)
    {
        $postToken = $request->request->get('token');

        if ($this->isCsrfTokenValid('credit_pay', $postToken)) {
            $gateway = $this->init();
            $mollie = $gateway['mollie_client'];

            if ( $user->getMollieCustomerId() == null )
            {
                $customer = $mollie->customers->create([
                    "name" => $user->getFirstname().' '.$user->getSurname(),
                    "email" => $user->getEmail()
                ]);

                $user = $entityManager->getRepository(User::class)->find($user->getId());

                $user->setMollieCustomerId($customer->id);
                $entityManager->flush();

            }

            $redirectUrl = "{$gateway['protocol']}://{$gateway['hostname']}/dashboard/user/mollie/add_customer_info";
            $amount = $this->getParameter('credit_amount') * $request->request->get('credit_package');

            $payload = [
                "amount" => [
                    "currency" => "EUR",
                    "value" => number_format($amount, 2)
                ],
                "description" => 'Credit package',
                "redirectUrl" => $redirectUrl,
                "metadata" => [
                    "user" => $user->getId(),
                    "package" => $request->request->get('credit_package')
                ],
            ];

            try {
                $payment = $mollie->customers->get($user->getMollieCustomerId())->createPayment($payload);
                return $this->redirect($payment->getCheckoutUrl());

            } catch (ApiException $e) {
                $this->addFlash('error', 'Fout in Mollie betaling');
                return $this->redirectToRoute('dashboard_credit');
            }

        } else {
            return $this->redirectToRoute('dashboard_credit');
        }
    }

    #[Route(path: ['en' => '/dashboard/user/mollie/add_customer_info', 'nl' => '/dashboard/user/mollie/add_customer_info', 'fr' => '/dashboard/user/mollie/add_customer_info'], name: 'dashboard_user_add_mollie_info')]
    public function userAddMollieCustomerInfo(
        Request $request,
        UserInterface $user,
        MailerInterface $mailer,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    )
    {
        $gateway = $this->init();
        /** @var MollieApiClient $mollie */
        $mollie = $gateway['mollie_client'];

        $payments = $mollie->customers->get($user->getMollieCustomerId())?->payments();

        if ( $payments->count > 0 )
        {
            $lastPayment = $payments[0];
            if ($lastPayment->isPaid() && !$lastPayment->hasRefunds() && !$lastPayment->hasChargebacks())
            {
                $this->transactionStore($lastPayment, $user);

                $totalCredit = $user->getCredits() + $lastPayment->metadata->package*1;
                $user->setCredits($totalCredit);
                $entityManager->flush();

                $lastInvoice = $entityManager->getRepository(Invoice::class)->findOneBy([], ['id' => 'DESC']);
                $nextInvoiceNumber = $lastInvoice ? $lastInvoice->getNumber() + 1 : 520;

                $invoice = new Invoice();
                $invoice->setNumber($nextInvoiceNumber);
                $invoice->setInvoiceDate(new DateTime());
                $invoice->setUserId($user->getId());

                $vatPercent = $this->getParameter('vat_percent');

                $vatAmount = 0;
                if ($vatPercent > 0) {
                    $vatAmount = ($lastPayment->amount->value * $vatPercent) / 100;
                }

                $vatAmount = round($vatAmount, 2);
                $totalHT = $lastPayment->amount->value - $vatAmount;
                $invoice->setTotalHt($totalHT);
                $invoice->setTotalTax($vatAmount);
                $invoice->setTotalTTC($lastPayment->amount->value);

                $entityManager->persist($invoice);
                $entityManager->flush();


                $invoiceDetail = new InvoiceDetail();
                $invoiceDetail->setInvoice($invoice);
                $invoiceDetail->setEventadvertTitle('Credit Payment');
                $invoiceDetail->setTransactionId($lastPayment->id);
                $invoiceDetail->setQuantity(1);
                $invoiceDetail->setEventadvertFeeAmount($vatAmount);
                $invoiceDetail->setPublicationDate(new DateTime());
                $invoiceDetail->setTotalAmount($lastPayment->amount->value);

                $entityManager->persist($invoiceDetail);
                $entityManager->flush();

                // NEW: Send email to user
//                $emailService = new EmailService($mailer);

                $subject = "Uw aankoop op Promotip";
                $locale = $request->getLocale();
                if ($locale === 'en') {
                    $locale = 'nl';
                }
                $country = strtoupper($this->getParameter('country'));
                $userCompany = $companyRepository->findOneBy(['userId' => $user->getId()]);
                $invoiceNumber = $invoice->getInvoiceDate()->format('Y').'-'.str_pad($invoice->getNumber(),4,"0",STR_PAD_LEFT);
                $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
                    'iso' => $country,
                    'language' => $locale,
                    'id' => ($userCompany) ? $userCompany->getGeoPlacesId() : ''
                ]);
                $vat_percent = $this->getParameter('vat_percent');

                $invoiceHtml = $this->renderView('dashboard/facturen/single.html.twig', [
                    'balance' => $user->getCredits(),
                    'user' => $user,
                    'invoice' => $invoice,
                    'company' => $userCompany,
                    'invoiceNumber' => $invoiceNumber,
                    'city' => $geoCity,
                    'vat' => $vat_percent
                ]);


                $emailService->emailInvoice(
                    $invoiceHtml,
                    $subject,
                    $user,
                    $invoice,
                    $invoiceDetail
                );
            }
        }

        return $this->redirectToRoute('dashboard_credit');

    }

    public function init()
    {
        $mollie = new MollieApiClient();
        $mollie->setApiKey($this->getParameter('mollie_key'));
        $protocol = isset($_SERVER['HTTPS']) && strcasecmp('off', $_SERVER['HTTPS']) !== 0 ? "https" : "http";
        $hostname = $_SERVER['HTTP_HOST'];

        return ['mollie_client' => $mollie, 'protocol' => $protocol, 'hostname' => $hostname];
    }

    public function transactionStore(
        $payment_intent,
        User|UserInterface $user
    )
    {

        if (is_null($payment_intent->method)) {
            $payment_intent->method = 'none';
        }

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setPaymentMethod($payment_intent->method);
        $transaction->setServiceTransactionId($payment_intent->id);
        $transaction->setAmount($payment_intent->amount->value);
        $transaction->setDatePayment(new DateTime($payment_intent->createdAt));

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }
}
