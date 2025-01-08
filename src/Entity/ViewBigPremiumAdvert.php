<?php

namespace App\Entity;

use App\Repository\ViewBigPremiumAdvertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ViewBigPremiumAdvertRepository::class)]
class ViewBigPremiumAdvert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $eventPremiumId;

    #[ORM\Column(type: 'integer')]
    private $views;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventPremiumId(): ?int
    {
        return $this->eventPremiumId;
    }

    public function setEventPremiumId(int $eventPremiumId): self
    {
        $this->eventPremiumId = $eventPremiumId;

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
