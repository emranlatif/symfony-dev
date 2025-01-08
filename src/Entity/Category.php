<?php

namespace App\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

#[ORM\Entity(repositoryClass: \App\Repository\CategoryRepository::class)]
class Category implements Translatable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Gedmo\Locale]
    private $locale;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $channel;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $parent;

    #[Gedmo\Translatable]
    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[Gedmo\Slug(fields: ["title"])]
    #[Gedmo\Translatable]
    #[ORM\Column(length: 255, unique: true)]
    private $titleSlug;

    #[Gedmo\Translatable]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $featured;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?int
    {
        return $this->channel;
    }

    public function setChannel(?int $channel): self
    {
        $this->channel = $channel;

        return $this;
    }


    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(?int $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getFeatured(): ?int
    {
        return $this->featured;
    }

    public function setFeatured(?int $featured): self
    {
        $this->featured = $featured;

        return $this;
    }

    public function getTitleSlug()
    {
        return $this->titleSlug;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
