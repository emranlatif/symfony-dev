<?php

namespace App\Command;

use App\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(
    name: "promotip:fix-invoice-tax",description: "Fixes invoice tax issue"
)]
class FixInvoiceTaxCommand extends Command
{
    private $entityManager;

    private $container;

    private $promotipExtension;

    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container
    )
    {
        $this->entityManager = $entityManager;
        $this->container = $container;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $invoices = $this->entityManager->getRepository(Invoice::class)->findBy([]);
        foreach($invoices as $invoice){
            $vatPercent = $this->container->getParameter('vat_percent');
            $vatAmount = 0;
            $subTotal = $invoice->getTotalTTC();
            if ( $vatPercent > 0 ) {
                $subTotal = $invoice->getTotalTTC() /(($vatPercent+100)/100);
                $vatAmount = $invoice->getTotalTTC() - $subTotal;
            }
            $vatAmount = round($vatAmount, 2);
            $totalHT = $subTotal;
            $invoice->setTotalHt($totalHT);
            $invoice->setTotalTax($vatAmount);

            $this->entityManager->persist($invoice);


            foreach($invoice->getInvoiceDetails() as $detail){
                $detail->setEventadvertFeeAmount($vatAmount);
            }
            $this->entityManager->flush();

            $io->success(sprintf('Fixed #%s', $invoice->getId()));
        }

        $io->success('Fixed all invoices');

        return Command::SUCCESS;
    }
}
