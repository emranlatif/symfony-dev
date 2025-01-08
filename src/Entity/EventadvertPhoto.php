<?php

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\Entity]
class EventadvertPhoto
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Eventadvert::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: true)]
    private $eventAdvert;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     *
     * @var File|null
     */
    private $imageFile;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string')]
    private $imageName;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer')]
    private $imageSize;

    /**
     * @var DateTimeInterface|null
     */
    #[ORM\Column(type: 'datetime')]
    private $updatedAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $priority;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private $temporaryId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $imageAlt;


    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getEventAdvert(): ?EventAdvert
    {
        return $this->eventAdvert;
    }

    public function setEventAdvert(?EventAdvert $eventAdvert): self
    {
        $this->eventAdvert = $eventAdvert;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getTemporaryId(): ?string
    {
        return $this->temporaryId;
    }

    public function setTemporaryId(?string $temporaryId): self
    {
        $this->temporaryId = $temporaryId;

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

    public function getImageAlt(): ?string
    {
        return $this->imageAlt;
    }

    public function setImageAlt(?string $imageAlt): self
    {
        $this->imageAlt = $imageAlt;

        return $this;
    }
}

