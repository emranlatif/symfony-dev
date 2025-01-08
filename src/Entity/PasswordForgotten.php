<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PasswordForgottenRepository::class)]
class PasswordForgotten
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private $hash;

    #[ORM\Column(type: 'datetime')]
    private $requestDate;

    #[ORM\Column(type: 'string', length: 64)]
    private $requestIpaddr;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $changeDate;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $changeIpaddr;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getRequestDate(): ?DateTimeInterface
    {
        return $this->requestDate;
    }

    public function setRequestDate(DateTimeInterface $requestDate): self
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    public function getRequestIpaddr(): ?string
    {
        return $this->requestIpaddr;
    }

    public function setRequestIpaddr(string $requestIpaddr): self
    {
        $this->requestIpaddr = $requestIpaddr;

        return $this;
    }

    public function getChangeDate(): ?DateTimeInterface
    {
        return $this->changeDate;
    }

    public function setChangeDate(?DateTimeInterface $changeDate): self
    {
        $this->changeDate = $changeDate;

        return $this;
    }

    public function getChangeIpaddr(): ?string
    {
        return $this->changeIpaddr;
    }

    public function setChangeIpaddr(?string $changeIpaddr): self
    {
        $this->changeIpaddr = $changeIpaddr;

        return $this;
    }
}
