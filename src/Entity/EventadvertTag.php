<?php

namespace App\Entity;

use App\Repository\EventadvertTagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventadvertTagRepository::class)]
class EventadvertTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Eventadvert::class, inversedBy: 'eventadvertTags')]
    private $advert;

    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'eventadvertTags')]
    private $tag;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdvert(): ?Eventadvert
    {
        return $this->advert;
    }

    public function setAdvert(?Eventadvert $advert): self
    {
        $this->advert = $advert;

        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }
}
