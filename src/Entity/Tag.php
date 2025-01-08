<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

#[ORM\Entity(repositoryClass: \App\Repository\TagRepository::class)]
class Tag implements Translatable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Gedmo\Locale]
    private $locale;

    #[Gedmo\Translatable]
    #[ORM\Column(type: 'string', length: 64)]
    private $name;


    #[Gedmo\Slug(fields: ["name"])]
    #[Gedmo\Translatable]
    #[ORM\Column(length: 255, unique: true)]
    private $nameSlug;

    #[ORM\OneToMany(targetEntity: CompanyTag::class, mappedBy: 'tag')]
    private $companyTags;

    #[ORM\OneToMany(targetEntity: EventadvertTag::class, mappedBy: 'tag')]
    private $eventadvertTags;


    public function __construct()
    {
        $this->companyTags = new ArrayCollection();
        $this->eventadvertTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNameSlug(): ?string
    {
        return $this->nameSlug;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return Collection<int, CompanyTag>
     */
    public function getCompanyTags(): Collection
    {
        return $this->companyTags;
    }

    public function addCompanyTag(CompanyTag $companyTag): self
    {
        if (!$this->companyTags->contains($companyTag)) {
            $this->companyTags[] = $companyTag;
            $companyTag->setTag($this);
        }

        return $this;
    }

    public function removeCompanyTag(CompanyTag $companyTag): self
    {
        if ($this->companyTags->removeElement($companyTag)) {
            // set the owning side to null (unless already changed)
            if ($companyTag->getTag() === $this) {
                $companyTag->setTag(null);
            }
        }

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
            $eventadvertTag->setTag($this);
        }

        return $this;
    }

    public function removeEventadvertTag(EventadvertTag $eventadvertTag): self
    {
        if ($this->eventadvertTags->removeElement($eventadvertTag)) {
            // set the owning side to null (unless already changed)
            if ($eventadvertTag->getTag() === $this) {
                $eventadvertTag->setTag(null);
            }
        }

        return $this;
    }


}
