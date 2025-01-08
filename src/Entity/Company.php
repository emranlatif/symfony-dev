<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Ddeboer\VatinBundle\Validator\Constraints\Vatin;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company implements Translatable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Gedmo\Locale]
    private $locale;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $userId;

    #[ORM\OneToOne(targetEntity: User::class)]
    private $user;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 72, minMessage: 'Your company name must be at least {{ limit }} characters long', maxMessage: 'Your company name cannot be longer than {{ limit }} characters'),]
    private $companyname;

    #[Gedmo\Slug(fields: ["companyname"])]
    #[ORM\Column(length: 255, unique: true)]
    private $companynameSlug;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Your address must be at least {{ limit }} characters long', maxMessage: 'Your address cannot be longer than {{ limit }} characters')]
    private $address;

    #[ORM\Column(type: 'string', length: 8)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 8, minMessage: 'Your housenumber must be at least {{ limit }} characters long', maxMessage: 'Your housenumber cannot be longer than {{ limit }} characters')]
    private $housenumber;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    #[Assert\AtLeastOneOf([
        new Assert\Length(min: 1, max: 8, minMessage: 'Your box must be at least {{ limit }} characters long', maxMessage: 'Your box cannot be longer than {{ limit }} characters'),
        new Assert\Blank()
    ])]

    private $box;

    #[ORM\Column(type: 'bigint', nullable: false)]
    #[Assert\AtLeastOneOf([
        new Assert\Length(min: 1, max: 24, minMessage: 'Please select your location', maxMessage: 'Please select your location'),
        new Assert\Blank()
    ])]
    private $geoPlacesId;


    #[AssertPhoneNumber(defaultRegion: "BE")]
    #[ORM\Column(type: 'phone_number', nullable: true)]
    private $phonenumber;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    private $emailaddress;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url(message: "The url '{{ value }}' is not a valid url", protocols: ['http', 'https'])]
    private $website;

    #[Vatin]
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $vatnumber;

    #[Gedmo\Translatable]
    #[ORM\Column(type: 'text')]
    #[Assert\Length(min: 24, max: 6000, minMessage: 'Your introduction must be at least {{ limit }} characters long', maxMessage: 'Your introduction cannot be longer than {{ limit }} characters')]
    private $description;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private $status;


    #[ORM\Column(type: 'float', nullable: true)]
    private $longitude;

    #[ORM\Column(type: 'float', nullable: true)]
    private $latitude;

    #[ORM\Column(type: 'datetime')]
    private $creationDate;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $creationIpaddr;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updateDate;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $updateDateIpaddr;

    #[ORM\Column(type: 'text', nullable: true)]
    private $enrichment;

    #[ORM\OneToMany(targetEntity: CompanyPhoto::class, mappedBy: 'company', orphanRemoval: true)]
    private $photos;

    #[ORM\OneToMany(targetEntity: OpeningHour::class, mappedBy: 'company', orphanRemoval: true)]
    private $openingHour;

    #[ORM\OneToMany(targetEntity: CompanyTag::class, mappedBy: 'company')]
    private $companyTags;

    #[ORM\OneToMany(targetEntity: Eventadvert::class, mappedBy: 'company')]
    private $eventadverts;

    #[ORM\Column(type: 'string', columnDefinition: "enum('pending', 'paid')")]
    private $paymentStatus = 'pending';

    #[ORM\Column(type: 'smallint', nullable: true, options: ['default' => 0])]
    private $only_appointment;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['default' => 0])]
    private $webshop_only;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isClaimed;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->openingHour = new ArrayCollection();
        $this->companyTags = new ArrayCollection();
        $this->eventadverts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return self
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }


    public function getCompanyname(): ?string
    {
        return $this->companyname;
    }

    public function setCompanyname(string $companyname): self
    {
        $this->companyname = $companyname;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getHousenumber(): ?string
    {
        return $this->housenumber;
    }

    public function setHousenumber(string $housenumber): self
    {
        $this->housenumber = $housenumber;

        return $this;
    }

    public function getBox(): ?string
    {
        return $this->box;
    }

    public function setBox(?string $box): self
    {
        $this->box = $box;

        return $this;
    }


    public function getEmailaddress(): ?string
    {
        return $this->emailaddress;
    }

    public function setEmailaddress(?string $emailaddress): self
    {
        $this->emailaddress = $emailaddress;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getVatnumber(): ?string
    {
        return $this->vatnumber;
    }

    public function setVatnumber(?string $vatnumber): self
    {
        $this->vatnumber = $vatnumber;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getGeoPlacesId(): ?int
    {
        return $this->geoPlacesId;
    }

    public function setGeoPlacesId(?int $geoPlacesId): self
    {
        $this->geoPlacesId = $geoPlacesId;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getCreationDate(): ?DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationIpaddr(): ?string
    {
        return $this->creationIpaddr;
    }

    public function setCreationIpaddr(string $creationIpaddr): self
    {
        $this->creationIpaddr = $creationIpaddr;

        return $this;
    }

    public function getUpdateDate(): ?DateTimeInterface
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?DateTimeInterface $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getUpdateDateIpaddr(): ?string
    {
        return $this->updateDateIpaddr;
    }

    public function setUpdateDateIpaddr(string $updateDateIpaddr): self
    {
        $this->updateDateIpaddr = $updateDateIpaddr;

        return $this;
    }

    public function getEnrichment(): ?string
    {
        return $this->enrichment;
    }

    public function setEnrichment($enrichment): self
    {
        $this->enrichment = $enrichment;
        return $this;
    }

    /**
     * @return Collection|CompanyPhoto[]
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(CompanyPhoto $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
            $photo->setCompanyId($this);
        }

        return $this;
    }

    public function removePhoto(CompanyPhoto $photo): self
    {
        if ($this->photos->contains($photo)) {
            $this->photos->removeElement($photo);
            // set the owning side to null (unless already changed)
            if ($photo->getCompanyId() === $this) {
                $photo->setCompanyId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OpeningHour[]
     */
    public function getOpeningHour(): Collection
    {
        return $this->openingHour;
    }

    public function addOpeningHour(OpeningHour $openingHour): self
    {
        if (!$this->openingHour->contains($openingHour)) {
            $this->openingHour[] = $openingHour;
            $openingHour->setCompanyId($this);
        }

        return $this;
    }

    public function removeOpeningHour(OpeningHour $openingHour): self
    {
        if ($this->openingHour->contains($openingHour)) {
            $this->openingHour->removeElement($openingHour);
            // set the owning side to null (unless already changed)
            if ($openingHour->getCompanyId() === $this) {
                $openingHour->setCompanyId(null);
            }
        }

        return $this;
    }

    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    public function setPhonenumber($phonenumber): self
    {
        $this->phonenumber = $phonenumber;

        return $this;
    }

    public function getCompanynameSlug(): ?string
    {
        return $this->companynameSlug;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return Collection|CompanyTag[]
     */
    public function getCompanyTags(): Collection
    {
        return $this->companyTags;
    }

    public function addCompanyTag(CompanyTag $companyTag): self
    {
        if (!$this->companyTags->contains($companyTag)) {
            $this->companyTags[] = $companyTag;
            $companyTag->setCompany($this);
        }

        return $this;
    }

    public function removeCompanyTag(CompanyTag $companyTag): self
    {
        if ($this->companyTags->contains($companyTag)) {
            $this->companyTags->removeElement($companyTag);
            // set the owning side to null (unless already changed)
            if ($companyTag->getCompany() === $this) {
                $companyTag->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Eventadvert[]
     */
    public function getEventadverts(): Collection
    {
        return $this->eventadverts;
    }

    public function addEventadvert(Eventadvert $eventadvert): self
    {
        if (!$this->eventadverts->contains($eventadvert)) {
            $this->eventadverts[] = $eventadvert;
            $eventadvert->setCompany($this);
        }

        return $this;
    }

    public function removeEventadvert(Eventadvert $eventadvert): self
    {
        if ($this->eventadverts->contains($eventadvert)) {
            $this->eventadverts->removeElement($eventadvert);
            // set the owning side to null (unless already changed)
            if ($eventadvert->getCompany() === $this) {
                $eventadvert->setCompany(null);
            }
        }

        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getOnlyAppointment(): ?int
    {
        return $this->only_appointment;
    }

    public function setOnlyAppointment(?int $only_appointment): self
    {
        $this->only_appointment = $only_appointment;

        return $this;
    }

    public function getWebshopOnly(): ?int
    {
        return $this->webshop_only;
    }

    public function setWebshopOnly(?int $webshop_only): self
    {
        $this->webshop_only = $webshop_only;

        return $this;
    }

    public function isClaimed(): ?bool
    {
        return $this->isClaimed;
    }

    public function setClaimed(?bool $isClaimed): static
    {
        $this->isClaimed = $isClaimed;
        return $this;
    }



}
