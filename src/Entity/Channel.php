<?php

namespace App\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

#[ORM\Entity(repositoryClass: \App\Repository\ChannelRepository::class)]
class Channel implements Translatable
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

    #[ORM\Column(type: 'string', length: 64)]
    private $route;

    #[Gedmo\Slug(fields: ["name"])]
    #[Gedmo\Translatable]
    #[ORM\Column(length: 255, unique: true)]
    private $nameSlug;

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

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

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
}
