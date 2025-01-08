<?php
namespace App\Service;

use App\Entity\GeoPlaces;
use App\Repository\InvoiceRepository;
use App\Repository\CompanyRepository;
use App\Twig\PromotipTwigExtension;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

class InvoiceService
{
    private $invoiceRepository;

    private $companyRepository;

    private $promotipTwigExtension;

    private $requestStack;

    private $container;

    private $entityManager;

    private $environment;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        CompanyRepository $companyRepository,
        PromotipTwigExtension $promotipTwigExtension,
        RequestStack $requestStack,
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        Environment $environment
    ){
        $this->invoiceRepository = $invoiceRepository;
        $this->companyRepository = $companyRepository;
        $this->promotipTwigExtension = $promotipTwigExtension;
        $this->requestStack = $requestStack;
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->environment = $environment;
    }

    public function createInvoice($invoiceId, $user)
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        if ($locale == 'en') {
            $locale = 'nl';
        }

        $targetDir = $this->container->getParameter('kernel.project_dir').'/public/media';
        if(!is_dir($targetDir)){
            mkdir($targetDir, 0777, true);
        }

        $country = strtoupper($this->container->getParameter('country'));

        $invoice = $this->invoiceRepository->find($invoiceId);

        $invoiceNumber = $this->promotipTwigExtension->getFrontInvoiceNumber($invoice);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled',true);

        $pdf = new Dompdf($options);

        $userCompany = $this->companyRepository->findOneBy(['userId' => $user->getId()]);

        $vat_percent = $this->container->getParameter('vat_percent');

        $geoCity = $this->entityManager->getRepository(GeoPlaces::class)->findOneBy([
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


        $html = $this->environment->render('dashboard/facturen/single.html.twig', [
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

        file_put_contents($targetDir.'/'.$invoiceNumber.'.pdf', $content);

        return $targetDir.'/'.$invoiceNumber.'.pdf';
    }
}