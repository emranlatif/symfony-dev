<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TestRepository::class)]
class Test
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToOne(targetEntity: \App\Entity\Category::class, cascade: ['persist', 'remove'])]
    private $ew;

    #[ORM\OneToOne(targetEntity: \App\Entity\Test::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $parent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEw(): ?Category
    {
        return $this->ew;
    }

    public function setEw(?Category $ew): self
    {
        $this->ew = $ew;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
