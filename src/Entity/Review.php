<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    private $message;

    #[ORM\Column(type: 'integer', length: 1)]
    private $stars;

    #[ORM\Column(type: 'integer', length: 1)]
    private $approved;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'companyReviews')]
    private $company;

    #[ORM\ManyToOne(targetEntity: Visitor::class, inversedBy: 'visitorReviews')]
    private $visitor;


    public function __construct()
    {
        $this->company = new ArrayCollection();
        $this->visitor = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getStars(): ?string
    {
        return $this->stars;
    }

    public function setStars(string $stars): self
    {
        $this->stars = $stars;

        return $this;
    }

    public function getVisitor()
    {
        return $this->visitor;
    }


    public function setVisitor($value): self
    {
        $this->visitor = $value;
        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }


    public function setCompany($value): self
    {
        $this->company = $value;
        return $this;
    }

    public function getApproved(): bool
    {
        return $this->approved;
    }


    public function setApproved($value): self
    {
        $this->approved = $value;
        return $this;
    }
}