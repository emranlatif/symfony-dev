<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ORM\Entity(repositoryClass: \App\Repository\MessagesRepository::class)]
#[ORM\Table(name: 'messages')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[orm\Column(type: 'string')]
    private $received;

    #[ORM\Column(type: 'string')]
    private $sender;

    #[ORM\Column(type: 'integer')]
    private $companyId;

    
    #[ORM\Column(type: 'string')]
    private $message;

    #[ORM\Column(type: 'boolean')]
    private $isRead;

    #[ORM\Column(type: 'string')]
    private $subject;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param mixed $sender
     */
    public function setSender($sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return mixed
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * @param mixed $companyId
     */
    public function setCompanyId($companyId): void
    {
        $this->companyId = $companyId;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * @param mixed $isRead
     */
    public function setIsRead($isRead): void
    {
        $this->isRead = $isRead;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getReceived()
    {
        return $this->received;
    }

    /**
     * @param mixed $received
     */
    public function setReceived($received): void
    {
        $this->received = $received;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('sender', new Assert\NotBlank([
            'message' => 'email.notblank'
        ]));
        $metadata->addPropertyConstraint('sender', new Assert\Email([
            'message' => 'email.invalidFormat',
        ]));

        $metadata->addPropertyConstraint('subject', new Assert\NotBlank([
            'message' => 'subject.notblank'
        ]));

        $metadata->addPropertyConstraint('message', new Assert\NotBlank([
            'message' => 'message.notblank'
        ]));
    }
}
