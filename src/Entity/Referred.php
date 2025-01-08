<?php

namespace App\Entity;

use App\Repository\ReferredRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferredRepository::class)]
class Referred
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $parentUser;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $childUser;

    #[ORM\Column(type: 'string', nullable: true)]
    private $uuid;

    #[ORM\OneToMany(targetEntity: Reward::class, mappedBy: 'referred')]
    private $rewards;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $ipAddress;

    #[ORM\Column(type: 'text', nullable: true)]
    private $userAgent;

    #[ORM\Column(type: 'text', nullable: true)]
    private $httpReferrer;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isProcessed;

    public function __construct()
    {
        $this->rewards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentUser(): ?User
    {
        return $this->parentUser;
    }

    public function setParentUser(?User $parentUser): self
    {
        $this->parentUser = $parentUser;

        return $this;
    }

    public function getChildUser(): ?User
    {
        return $this->childUser;
    }

    public function setChildUser(?User $childUser): self
    {
        $this->childUser = $childUser;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection<int, Reward>
     */
    public function getRewards(): Collection
    {
        return $this->rewards;
    }

    public function addReward(Reward $reward): self
    {
        if (!$this->rewards->contains($reward)) {
            $this->rewards[] = $reward;
            $reward->setReferred($this);
        }

        return $this;
    }

    public function removeReward(Reward $reward): self
    {
        if ($this->rewards->removeElement($reward)) {
            // set the owning side to null (unless already changed)
            if ($reward->getReferred() === $this) {
                $reward->setReferred(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getHttpReferrer(): ?string
    {
        return $this->httpReferrer;
    }

    public function setHttpReferrer(?string $httpReferrer): self
    {
        $this->httpReferrer = $httpReferrer;

        return $this;
    }

    public function getIsProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(?bool $isProcessed): self
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }
}