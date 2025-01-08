<?php

namespace App\EventListener;

use App\Entity\CompanyPhoto;
use App\Entity\EventadvertPhoto;
use App\Entity\PremiumEventadvertPhoto;
use App\Repository\CompanyRepository;
use App\Repository\EventadvertRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UploadListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    private $userId;

    private $session;

    private $company = false;

    private $eventAdvert = false;

    private $photoType = null;
    private $photoPriority = null;

    public function __construct(
        EntityManagerInterface        $entityManager,
        TokenStorageInterface         $tokenStorage,
        private readonly RequestStack $requestStack,
        CompanyRepository             $companyRepository,
        EventadvertRepository         $eventadvertRepository
    )
    {
        $this->session = $this->requestStack->getSession();

        $this->entityManager = $entityManager;

        if ($tokenStorage->getToken()->getUser() != "anon.") {
            $this->userId = $tokenStorage->getToken()->getUser()->getId();
            $this->company = $companyRepository->findOneBy(['userId' => $this->userId]);
        }

        $request = $requestStack->getCurrentRequest();

        $this->photoType = $request->request->get('type');
        $this->photoPriority = $request->request->get('priority');

        if ($request->request->get('eventId') && $request->request->get('eventId') > 0) {
            $this->eventAdvert = $eventadvertRepository->findOneBy([
                'id' => $request->get('eventId'),
                //'userId' => $this->userId
            ]);
        }
    }

    public function onUpload(PostPersistEvent $event)
    {
        if (($this->eventAdvert !== false && $this->eventAdvert->getId() > 0) || $this->photoType == 'eventadvert') {
            $cp = new EventadvertPhoto();
            //$cp->setEventadvert($this->eventAdvert);

        } elseif ($this->photoType == 'premium_eventadvert') {
            $cp = new PremiumEventadvertPhoto();
        } else {
            $cp = new CompanyPhoto();
            //$cp->setCompany($this->company);
        }


        $temporaryId = bin2hex(random_bytes(10));
        $cp->setTemporaryId($temporaryId);
        $cp->setImageName($event->getFile()->getFilename());
        $cp->setImageSize($event->getFile()->getSize());

        $imgOriginalName = $event->getRequest()->files->get('file')->getClientOriginalName();
        $imgNameWithoutExtension = pathinfo($imgOriginalName, PATHINFO_FILENAME);
        $search = ['-', '_'];
        $replace = [' ', ' '];

        $imgAlt = str_replace($search, $replace, $imgNameWithoutExtension);

        $cp->setImageAlt($imgAlt);
        $cp->setUpdatedAt(new DateTime());
        $cp->setPriority($this->photoPriority ?? 99);
        if ($this->photoType != 'premium_eventadvert') $cp->setCompany($this->company);

        $this->entityManager->persist($cp);
        $this->entityManager->flush();


        //if everything went fine
        $response = $event->getResponse();
        $response['id'] = (int)$cp->getId();
        $response['temporaryId'] = (string)$temporaryId;
        $response['file'] = (string)$event->getFile()->getFilename();
        $response['size'] = (int)$event->getFile()->getSize();
        $response['alt'] = (string)$imgAlt;
        $response['success'] = (bool)true;

        $photos = [];
        if (($data = $this->session->get('photos_' . $this->photoType, false)) !== false) {
            $photos = $data;
        }
        $photos[] = $response;

        $this->session->set('photos_' . $this->photoType, $photos);

        return $response;
    }
}
