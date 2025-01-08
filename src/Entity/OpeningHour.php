<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\OpeningHourRepository::class)]
class OpeningHour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;


    #[ORM\Column(type: 'smallint')]
    private $day;

    #[ORM\Column(type: 'time')]
    private $openFrom;

    #[ORM\Column(type: 'time')]
    private $openTill;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'openingHour')]
    #[ORM\JoinColumn(nullable: false)]
    private $company;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getOpenFrom(): ?DateTimeInterface
    {
        return $this->openFrom;
    }

    public function setOpenFrom(DateTimeInterface $openFrom): self
    {
        $this->openFrom = $openFrom;

        return $this;
    }

    public function getOpenTill(): ?DateTimeInterface
    {
        return $this->openTill;
    }

    public function setOpenTill(DateTimeInterface $openTill): self
    {
        $this->openTill = $openTill;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }
}
