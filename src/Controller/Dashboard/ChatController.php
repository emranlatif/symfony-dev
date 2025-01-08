<?php

namespace App\Controller\Dashboard;

use App\Entity\Company;
use App\Entity\Message;
use App\Repository\MessagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;


class ChatController extends AbstractController
{
    #[Route(path: ['en' => '/dashboard/chat/', 'nl' => '/dashboard/berichten/', 'fr' => '/dashboard/messagerie/'], name: 'dashboard_chat')]
    public function index(Request $request, UserInterface $user, CompanyRepository $companyRepository, MessagesRepository $messagesRepository)
    {
        $messageList = [];

        /** @var Company $company */
        $company = $companyRepository->findOneBy(["userId" => $user->getId()]);

        if (is_null($company)) {
            return $this->redirectToRoute('dashboard_company', ['cf' => 'chat']);
        }

        $messageList = $messagesRepository->findBy(["companyId" => $company->getId()], ['id' => 'DESC']);

        return $this->render('dashboard/chat/index.html.twig', [
            'messageList' => $messageList,
            'balance' => $user->getCredits()
        ]);
    }

    #[Route(path: ['en' => '/dashboard/chat/{messageId}', 'nl' => '/dashboard/berichten/{messageId}', 'fr' => '/dashboard/messagerie/{messageId}'], name: 'dashboard_view_message')]
    public function viewMessage(int $messageId, MessagesRepository $messagesRepository, UserInterface $user, EntityManagerInterface $entityManager): Response
    {
        /** @var Message $message */
        $message = $messagesRepository->find($messageId);
        $messageList = $messageList = $messagesRepository->findBy(["companyId" => $message->getCompanyId()], ['id' => 'DESC']);

        $message->setIsRead(true);

        $entityManager->persist($message);
        $entityManager->flush();

        return $this->render('dashboard/chat/view.html.twig', [
            'messageId' => $message->getId(),
            'from' => $message->getSender(),
            'subject' => $message->getSubject(),
            'message' => $message->getMessage(),
            'received' => $message->getReceived(),
            'messageList' => $messageList,
            'balance' => $user->getCredits()
        ]);
    }

    #[Route(path: ['en' => '/dashboard/chat/delete/{messageId}', 'nl' => '/dashboard/berichten/delete/{messageId}', 'fr' => '/dashboard/messagerie/delete/{messageId}'], name: 'dashboard_delete_message')]
    public function deleteMessage(int $messageId, MessagesRepository $messagesRepository, UserInterface $user, EntityManagerInterface $entityManager): Response
    {
        /** @var Message $message */
        $message = $messagesRepository->find($messageId);

        $entityManager->remove($message);
        $entityManager->flush();

        $messageList = $messagesRepository->findBy(["companyId" => $message->getCompanyId()], ['id' => 'DESC']);

        return $this->render('dashboard/chat/index.html.twig', [
            'messageList' => $messageList,
            'balance' => $user->getCredits()
        ]);
    }
}
