<?php

namespace App\Entity;

use App\Repository\UserVerificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserVerificationRepository::class)]
#[UniqueEntity(fields: ['email'])]
class UserVerification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $surname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $proofOfOwnership = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyAction = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isProcessed = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    private ?Company $company;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getProofOfOwnership(): ?string
    {
        return $this->proofOfOwnership;
    }

    public function setProofOfOwnership(?string $proofOfOwnership): static
    {
        $this->proofOfOwnership = $proofOfOwnership;

        return $this;
    }

    public function getCompanyAction(): ?string
    {
        return $this->companyAction;
    }

    public function setCompanyAction(?string $companyAction): static
    {
        $this->companyAction = $companyAction;

        return $this;
    }

    public function isProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setProcessed(?bool $isProcessed): static
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): UserVerification
    {
        $this->company = $company;
        return $this;
    }

    public function match(){
        $total = 0;

        $source = $this->company->getUser()->getFirstname();
        $target = $this->name;
        similar_text($source, $target, $percent);
        $total += $percent;

        $source = $this->company->getCompanyname();
        $target = $this->getCompanyName();
        similar_text($source, $target, $percent);
        $total += $percent;

        $source = $this->company->getUser()->getSurname();
        $target = $this->getSurname();
        similar_text($source, $target, $percent);
        $total += $percent;

        $source = $this->company->getEmailaddress();
        $target = $this->getEmail();
        similar_text($source, $target, $percent);
        $total += $percent;

        if($total == 0){
            return 0;
        }

        return round($total / 4);
    }
}
