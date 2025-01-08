<?php

namespace App\Entity;

use App\Repository\ViewPremiumAdvertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ViewPremiumAdvertRepository::class)]
class ViewPremiumAdvert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $eventAdvertId;

    #[ORM\Column(type: 'integer')]
    private $views;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventAdvertId(): ?int
    {
        return $this->eventAdvertId;
    }

    public function setEventAdvertId(int $eventAdvertId): self
    {
        $this->eventAdvertId = $eventAdvertId;

        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(int $views): self
    {
        $this->views = $views;

        return $this;
    }
}
