<?php

namespace App\Entity;

use App\Repository\UserCreditRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCreditRepository::class)]
class UserCredit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $userId;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, options: ['default' => '0.00'])]
    private $creditBalance;

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

    public function getCreditBalance(): ?string
    {
        return $this->creditBalance;
    }

    public function setCreditBalance(string $creditBalance): self
    {
        $this->creditBalance = $creditBalance;

        return $this;
    }
}
