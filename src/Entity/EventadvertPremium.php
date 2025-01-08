<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

#[ORM\Entity(repositoryClass: \App\Repository\EventadvertPremiumRepository::class)]
class EventadvertPremium implements Translatable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $userId;

    #[Gedmo\Translatable]
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 128, minMessage: 'Your event title name must be at least {{ limit }} characters long', maxMessage: 'Your event title cannot be longer than {{ limit }} characters')]
    private $title;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private $creationDate;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $creationIpaddr;

    #[ORM\OneToMany(targetEntity: PremiumEventadvertPhoto::class, mappedBy: 'premiumEventAdvert', orphanRemoval: true)]
    #[ORM\OrderBy(['priority' => 'ASC'])]
    private $photos;

    #[ORM\Column(type: 'string', length: 255)]
    private $redirection_type;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, minMessage: 'Your event title name must be at least {{ limit }} characters long', maxMessage: 'Your event title cannot be longer than {{ limit }} characters')]
    private $redirection_link;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'premiumEventAdvert')]
    private $transactions;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $plan;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $paidDate;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $paid;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return EventadvertPremium
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     * @return EventadvertPremium
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return EventadvertPremium
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRedirectionType(): ?string
    {
        return $this->redirection_type;
    }

    /**
     * @param mixed $title
     * @return EventadvertPremium
     */
    public function setRedirectionType(string $redirection_type): self
    {
        $this->redirection_type = $redirection_type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRedirectionLink(): ?string
    {
        return $this->redirection_link;
    }

    /**
     * @param mixed $title
     * @return EventadvertPremium
     */
    public function setRedirectionLink(string $redirection_link): self
    {
        $this->redirection_link = $redirection_link;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param mixed $creationDate
     * @return EventadvertPremium
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationIpaddr()
    {
        return $this->creationIpaddr;
    }

    /**
     * @param mixed $creationIpaddr
     * @return EventadvertPremium
     */
    public function setCreationIpaddr($creationIpaddr)
    {
        $this->creationIpaddr = $creationIpaddr;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    /**
     * @param Collection $photos
     * @return EventadvertPremium
     */
    public function setPhotos(Collection $photos): EventadvertPremium
    {
        $this->photos = $photos;
        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setPremiumEventAdvertId($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getPremiumEventAdvertId() === $this) {
                $transaction->setPremiumEventAdvertId(null);
            }
        }

        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(?string $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getPaidDate(): ?DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(?DateTimeInterface $paidDate): self
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    public function getPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): self
    {
        $this->paid = $paid;

        return $this;
    }
}
