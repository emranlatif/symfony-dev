<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserVerification;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmailService
{

    private $mailer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }
    public function emailInvoice($invoiceHtml, $subject, $user, $invoice, $invoiceDetails)
    {
        try {

            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);

            $pdf = new Dompdf($options);

            $pdf->loadHtml($invoiceHtml);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            $pdfInvoice = $pdf->output();

            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate('dashboard/emails/invoice.html.twig')
                ->attach($pdfInvoice, $invoice->getInvoiceDate()->format('Y').'-'.str_pad($invoice->getNumber(), 4, "0", STR_PAD_LEFT) . ".pdf",
                'application/pdf');

            $this->mailer->send($email);
        } catch (Exception $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }

    public function claimEmail(
        UserVerification $userVerification
    ){

    }

    public function claimSuccessEmail($user, $loginLink){
        try {
            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Company claim approved')
                ->htmlTemplate('emails/html/claim-approved.html.twig')
                ->context([
                    'loginLink' => $loginLink
                ])
            ;

            $this->mailer->send($email);
        } catch (Exception $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }

    public function claimRejectEmail(UserVerification $userVerification)
    {
        try {
            $email = (new TemplatedEmail())
                ->to($userVerification->getEmail())
                ->subject('Company claim approved')
                ->htmlTemplate('emails/html/claim-rejected.html.twig')
                ->context([
                    'loginLink' => $this->urlGenerator->generate('claim_company', [
                        'companynameSlug' => $userVerification->getCompany()->getCompanynameSlug(),
                        'hash' => $userVerification->getVerificationToken()
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                ])
            ;

            $this->mailer->send($email);
        } catch (Exception $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }
}
