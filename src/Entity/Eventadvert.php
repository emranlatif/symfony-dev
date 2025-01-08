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

#[ORM\Entity(repositoryClass: \App\Repository\EventadvertRepository::class)]
class Eventadvert implements Translatable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Gedmo\Locale]
    private $locale;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $userId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventAdverts')]
    private $user;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'eventadverts', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private $company;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'Please select your channel')]
    private $channel;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'Please select your category')]
    private $category;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive(message: 'Please select your sub category')]
    private $subCategory;

    #[Gedmo\Translatable]
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 128, minMessage: 'Your event title name must be at least {{ limit }} characters long', maxMessage: 'Your event title cannot be longer than {{ limit }} characters')]
    private $title;

    #[ORM\Column(type: 'float', precision: 18, scale: 2, nullable: true)]
    private $price;

    #[Gedmo\Slug(fields: ["title"])]
    #[ORM\Column(length: 255, unique: true)]
    private $titleSlug;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\AtLeastOneOf([
        new Assert\Length(min: 2, max: 255, minMessage: 'Your address must be at least {{ limit }} characters long', maxMessage: 'Your address cannot be longer than {{ limit }} characters'),
        new Assert\Blank()
    ])]
    private $address;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    #[Assert\AtLeastOneOf([
        new Assert\Length(min: 1, max: 8, minMessage: 'Your housenumber must be at least {{ limit }} characters long', maxMessage: 'Your housenumber cannot be longer than {{ limit }} characters'),
        new Assert\Blank()
    ])]
    private $housenumber;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    #[Assert\AtLeastOneOf([
        new Assert\Length(min: 1, max: 8, minMessage: 'Your box must be at least {{ limit }} characters long', maxMessage: 'Your box cannot be longer than {{ limit }} characters'),
        new Assert\Blank()
    ])]
    private $box;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Assert\AtLeastOneOf([
        new Assert\Length(min: 1, max: 24, minMessage: 'Please select your location', maxMessage: 'Please select your location'),
        new Assert\Blank()
    ])]
    private $geoPlacesId;

    #[Gedmo\Translatable]
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 24, max: 6000, minMessage: 'Your description must be at least {{ limit }} characters long', maxMessage: 'Your description cannot be longer than {{ limit }} characters')]
    private $description;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private $status;

    #[ORM\Column(type: 'float', nullable: true)]
    private $longitude;

    #[ORM\Column(type: 'float', nullable: true)]
    private $latitude;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotBlank]
    private $eventStartDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private $eventEndDate;

    #[ORM\Column(type: 'time', nullable: true)]
    #[Assert\NotBlank]
    private $startHour;

    #[ORM\Column(type: 'time', nullable: true)]
    #[Assert\NotBlank]
    private $endHour;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private $creationDate;
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $creationIpaddr;

    #[ORM\OneToMany(targetEntity: EventadvertPhoto::class, mappedBy: 'eventAdvert', orphanRemoval: true)]
    #[ORM\OrderBy(['priority' => 'ASC'])]
    private $photos;

    #[ORM\Column(type: 'text', nullable: true)]
    private $enrichment;

    #[ORM\Column(type: 'bigint', nullable: false)]
    private $views;

    #[ORM\Column(type: 'string', columnDefinition: "enum('pending', 'paid')")]
    private $paymentStatus = 'pending';

    #[ORM\Column(type: 'smallint', nullable: true, options: ['default' => 0])]
    private $allDayEvent;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'eventAdvert')]
    private $transactions;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $plan;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $paidDate;

    #[ORM\OneToMany(targetEntity: EventadvertTag::class, mappedBy: 'advert')]
    private $eventadvertTags;

    #[ORM\OneToMany(targetEntity: CreditPayment::class, mappedBy: 'eventAdvert')]
    private $creditPayments;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $paused;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $blocked;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $deleted;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $deletedAt;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $premium = false;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->eventadvertTags = new ArrayCollection();
        $this->creditPayments = new ArrayCollection();
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
     * @return Eventadvert
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     * @return Eventadvert
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
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
     * @return Eventadvert
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     * @return Eventadvert
     */
    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param mixed $channel
     * @return Eventadvert
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     * @return Eventadvert
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    /**
     * @param mixed $category
     * @return Eventadvert
     */
    public function setSubCategory($category)
    {
        $this->subCategory = $category;
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
     * @return Eventadvert
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return null|float
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float $price
     *
     * @return Eventadvert
     */
    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitleSlug()
    {
        return $this->titleSlug;
    }

    /**
     * @param mixed $titleSlug
     * @return Eventadvert
     */
    public function setTitleSlug($titleSlug)
    {
        $this->titleSlug = $titleSlug;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     * @return Eventadvert
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHousenumber()
    {
        return $this->housenumber;
    }

    /**
     * @param mixed $housenumber
     * @return Eventadvert
     */
    public function setHousenumber($housenumber)
    {
        $this->housenumber = $housenumber;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBox()
    {
        return $this->box;
    }

    /**
     * @param mixed $box
     * @return Eventadvert
     */
    public function setBox($box)
    {
        $this->box = $box;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGeoPlacesId()
    {
        return $this->geoPlacesId;
    }

    /**
     * @param mixed $geoPlacesId
     * @return Eventadvert
     */
    public function setGeoPlacesId($geoPlacesId)
    {
        $this->geoPlacesId = $geoPlacesId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return Eventadvert
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return Eventadvert
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param mixed $longitude
     * @return Eventadvert
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param mixed $latitude
     * @return Eventadvert
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEventStartDate()
    {
        return $this->eventStartDate;
    }

    /**
     * @param mixed $eventStartDate
     * @return Eventadvert
     */
    public function setEventStartDate($eventStartDate)
    {
        $this->eventStartDate = $eventStartDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEventEndDate()
    {
        return $this->eventEndDate;
    }

    /**
     * @param mixed $eventEndDate
     * @return Eventadvert
     */
    public function setEventEndDate($eventEndDate)
    {
        $this->eventEndDate = $eventEndDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartHour()
    {
        return $this->startHour;
    }

    /**
     * @param mixed $startHour
     * @return Eventadvert
     */
    public function setStartHour($startHour)
    {
        $this->startHour = $startHour;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEndHour()
    {
        return $this->endHour;
    }

    /**
     * @param mixed $endHour
     * @return Eventadvert
     */
    public function setEndHour($endHour)
    {
        $this->endHour = $endHour;
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
     * @return Eventadvert
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
     * @return Eventadvert
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
     * @return Eventadvert
     */
    public function setPhotos(Collection $photos): Eventadvert
    {
        $this->photos = $photos;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEnrichment()
    {
        return $this->enrichment;
    }

    /**
     * @param mixed $enrichment
     * @return Eventadvert
     */
    public function setEnrichment($enrichment)
    {
        $this->enrichment = $enrichment;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param mixed $views
     * @return Eventadvert
     */
    public function setViews($views)
    {
        $this->views = $views;
        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getAllDayEvent(): ?int
    {
        return $this->allDayEvent;
    }

    public function setAllDayEvent(?int $allDayEvent): self
    {
        $this->allDayEvent = $allDayEvent;

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
            $transaction->setEventAdvertId($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getEventAdvertId() === $this) {
                $transaction->setEventAdvertId(null);
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

    /**
     * @return Collection<int, EventadvertTag>
     */
    public function getEventadvertTags(): Collection
    {
        return $this->eventadvertTags;
    }

    public function addEventadvertTag(EventadvertTag $eventadvertTag): self
    {
        if (!$this->eventadvertTags->contains($eventadvertTag)) {
            $this->eventadvertTags[] = $eventadvertTag;
            $eventadvertTag->setAdvert($this);
        }

        return $this;
    }

    public function removeEventadvertTag(EventadvertTag $eventadvertTag): self
    {
        if ($this->eventadvertTags->removeElement($eventadvertTag)) {
            // set the owning side to null (unless already changed)
            if ($eventadvertTag->getAdvert() === $this) {
                $eventadvertTag->setAdvert(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CreditPayment>
     */
    public function getCreditPayments(): Collection
    {
        return $this->creditPayments;
    }

    public function addCreditPayment(CreditPayment $creditPayment): self
    {
        if (!$this->creditPayments->contains($creditPayment)) {
            $this->creditPayments[] = $creditPayment;
            $creditPayment->setEventAdvert($this);
        }

        return $this;
    }

    public function removeCreditPayment(CreditPayment $creditPayment): self
    {
        if ($this->creditPayments->removeElement($creditPayment)) {
            // set the owning side to null (unless already changed)
            if ($creditPayment->getEventAdvert() === $this) {
                $creditPayment->setEventAdvert(null);
            }
        }

        return $this;
    }


    public function getPaused(): ?bool
    {
        return $this->paused;
    }

    public function setPaused(bool $paused): self
    {
        $this->paused = $paused;

        return $this;
    }

    public function getBlocked(): ?bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(DateTimeInterface $deletedAt = null): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isFreeAd(): bool
    {
        return ($this->plan === 'ONE_MONTH_FREE_ADVERT' or $this->plan === 'CREDIT_ONE_MONTH_FREE_ADVERT')
            or ($this->getStatus() == 1 and $this->getPaymentStatus() === 'pending');
    }


    public function getPremium(): ?bool
    {
        return $this->premium;
    }

    public function setPremium(bool $premium): self
    {
        $this->premium = $premium;

        return $this;
    }
    // Getter and setter for $user
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
