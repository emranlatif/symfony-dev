<?php

namespace App\Controller\Admin;

use App\Entity\GeoPlaces;
use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Repository\InvoiceRepository;
use App\Twig\PromotipTwigExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use ZipArchive;

class InvoiceController extends AbstractController
{

    #[Route(path: '/admin/invoices', name: 'admin_invoices')]
    public function index(
        UserInterface $user, InvoiceRepository $invoiceRepository, Request $request, CompanyRepository $companyRepository, EntityManagerInterface $entityManager, PromotipTwigExtension $promotipTwigExtension
    )
    {
        $builder = $this->createFormBuilder(null, [
            'method' => 'GET',
        ]);
        $builder->add('startDate', DateType::class, [
            'html5' => true,
            'widget' => 'single_text',
            'mapped' => false,
            'required' => false,
            'row_attr' => [
                'style' => 'gap:1rem'
            ]
        ]);
        $builder->add('endDate', DateType::class, [
            'html5' => true,
            'widget' => 'single_text',
            'mapped' => false,
            'required' => false,
            'row_attr' => [
                'style' => 'gap:1rem'
            ],
        ]);
        if (in_array('ROLE_ROOT', $user->getRoles())) {
            $builder->add('user', TextType::class, [
                'mapped' => false,
                'required' => false,
                'row_attr' => [
                    'style' => 'gap:1rem'
                ],
                'attr' => [
                    'placeholder' => 'Email or User ID'
                ],
                'data' => $request->query->all('form')['user'] ?? ''
            ]);
        }

        $form = $builder->getForm();

        $qb = $invoiceRepository->createQueryBuilder('invoice');
//        if(!in_array('ROLE_ROOT', $user->getRoles())){
//            $qb->andWhere('invoice.userId = :userId');
//            $qb->setParameter('userId', $user->getId());
//        }
//        $qb->andWhere('invoice.userId = :userId');
//        $qb->setParameter('userId', $user->getId());

        if ($request->query->has('form') && trim($request->query->all('form')['startDate'] ?? '') !== '') {
            $qb->andWhere('DATE(invoice.invoiceDate) >= :startDate')->setParameter('startDate', $request->query->all('form')['startDate']);
        }

        if ($request->query->has('form') && trim($request->query->all('form')['endDate'] ?? '') !== '') {
            $qb->andWhere('DATE(invoice.invoiceDate) <= :endDate')->setParameter('endDate', $request->query->all('form')['endDate']);
        }

        if (!in_array('ROLE_ROOT', $user->getRoles())) {
            $qb->andWhere('invoice.userId = :userId');
            $qb->setParameter('userId', $user->getId());
        } elseif ($request->query->has('form') && trim($request->query->all('form')['user'] ?? '') !== '') {
            $qb->join(User::class, 'user', Join::WITH, 'user.id = invoice.userId');
            $qb->andWhere('user.id = :user OR user.email = :user');
            $qb->setParameter('user', $request->query->all('form')['user']);
        }

        $invoices = $qb->getQuery()->getResult();
        if ($request->query->has('download')) {
            $locale = $request->getLocale();
            if ($locale === 'en') {
                $locale = 'nl';
            }

            $country = strtoupper($this->getParameter('country'));
            // create pdf files and add them in zip to download
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);

            $zip = new ZipArchive();
            $zipName = \uniqid() . '-invoices.zip';
            $zipPath = $this->getParameter('kernel.project_dir') . '/public/media/' . $zipName;
            $zip->open($zipPath, ZipArchive::CREATE);

            foreach ($invoices as $invoice) {
                $pdf = new Dompdf($options);
                $invoiceNumber = $promotipTwigExtension->getFrontInvoiceNumber($invoice);
                $userCompany = $companyRepository->findOneBy(['userId' => $invoice->getUserId()]);

                $vat_percent = $this->getParameter('vat_percent');

                $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
                    'iso' => ucfirst($country),
                    'language' => $locale,
                    'id' => $userCompany->getGeoPlacesId()
                ]);

                $html = $this->renderView('dashboard/facturen/single.html.twig', [
                    'balance' => $user->getCredits(),
                    'user' => $user,
                    'invoice' => $invoice,
                    'company' => $userCompany,
                    'invoiceNumber' => $invoiceNumber,
                    'city' => $geoCity,
                    'vat' => $vat_percent
                ]);

                $pdf->loadHtml($html);
                $pdf->setPaper('A4', 'portrait');
                $pdf->render();

                $output = $pdf->output();
                if (!is_dir($this->getParameter('kernel.project_dir') . '/public/media')) {
                    mkdir($this->getParameter('kernel.project_dir') . '/public/media', 0777, true);
                }
                $fileName = $this->getParameter('kernel.project_dir') . '/public/media/' . $invoiceNumber . '.pdf';
                \file_put_contents($fileName, $output);

                $zip->addFile($fileName, $invoiceNumber . '.pdf');

                // unlink($fileName);
            }

            $zip->close();

            // cleanup
            foreach ($invoices as $invoice) {
                $invoiceNumber = $promotipTwigExtension->getFrontInvoiceNumber($invoice);
                $fileName = $this->getParameter('kernel.project_dir') . '/public/media/' . $invoiceNumber . '.pdf';

                unlink($fileName);
            }

            return $this->file($zipPath, $zipName);
        }
        // $invoices = $invoiceRepository->findBy(['userId' => $user->getId()]);
        return $this->render(
            'admin/invoices/index.html.twig',
            [
                'balance' => $user->getCredits(),
                'user' => $user,
                'invoices' => $invoices,
                'form' => $form->createView()
            ]
        );
    }



    #[Route(path: '/admin/invoice/{invoice}', name: 'admin_invoice')]
    public function download(
        Request               $request,
        UserInterface         $user,
        InvoiceRepository     $invoiceRepository,
        CompanyRepository     $companyRepository,
        PromotipTwigExtension $promotipTwigExtension,
        int                   $invoice,
        EntityManagerInterface $entityManager
    )
    {

        $locale = $request->getLocale();
        if ($locale === 'en') {
            $locale = 'nl';
        }

        $targetDir = $this->getParameter('kernel.project_dir') . '/public/media';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }


        $country = strtoupper($this->getParameter('country'));

        $invoice = $invoiceRepository->find($invoice);

        $invoiceNumber = $promotipTwigExtension->getFrontInvoiceNumber($invoice);
        if (file_exists($targetDir . '/' . $invoiceNumber . '.pdf')) {
//            return $this->file($targetDir . '/' . $invoiceNumber . '.pdf');
        }

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $pdf = new Dompdf($options);

        $userCompany = $companyRepository->findOneBy(['userId' => $invoice->getUserId()]);

        $vat_percent = $this->getParameter('vat_percent');

        $geoCity = $entityManager->getRepository(GeoPlaces::class)->findOneBy([
            'iso' => ucfirst($country),
            'language' => $locale,
            'id' => $userCompany->getGeoPlacesId()
        ]);

        // return $this->render(
        //     'dashboard/facturen/single.html.twig',
        //     [
        //         'balance' => $user->getCredits(),
        //         'user' => $user,
        //         'invoice' => $invoice,
        //         'company' => $userCompany,
        //         'invoiceNumber' => $invoiceNumber,
        //         'city' => $geoCity,
        //         'vat' => $vat_percent
        //     ]
        // );


        $html = $this->renderView('dashboard/facturen/single.html.twig', [
            'balance' => $user->getCredits(),
            'user' => $user,
            'invoice' => $invoice,
            'company' => $userCompany,
            'invoiceNumber' => $invoiceNumber,
            'city' => $geoCity,
            'vat' => $vat_percent
        ]);

        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $content = $pdf->output();


        file_put_contents($targetDir . '/' . $invoiceNumber . '.pdf', $content);

        return $this->file($targetDir . '/' . $invoiceNumber . '.pdf');
    }
}
