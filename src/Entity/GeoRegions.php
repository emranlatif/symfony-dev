<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GeoRegions
 */
#[ORM\Entity]
#[ORM\Table(name: 'geo_regions')]
#[ORM\Index(name: 'iso', columns: ['iso'])]
#[ORM\Index(name: 'iso2', columns: ['iso2'])]
#[ORM\Index(name: 'iso_2', columns: ['iso', 'name', 'region_language'])]
#[ORM\Index(name: 'language', columns: ['language', 'iso2'])]
#[ORM\Index(name: 'name_dirify', columns: ['name_dirify'])]
class GeoRegions
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'iso', type: 'string', length: 2, nullable: false)]
    private $iso;

    /**
     * @var string
     */
    #[ORM\Column(name: 'country', type: 'string', length: 50, nullable: false)]
    private $country;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'language', type: 'string', length: 2, nullable: true)]
    private $language;

    /**
     * @var int
     */
    #[ORM\Column(name: 'level', type: 'smallint', nullable: false)]
    private $level;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string', length: 50, nullable: false)]
    private $type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 80, nullable: false)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name_dirify', type: 'string', length: 80, nullable: false)]
    private $nameDirify;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'region1', type: 'string', length: 80, nullable: true)]
    private $region1;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'region2', type: 'string', length: 80, nullable: true)]
    private $region2;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'region3', type: 'string', length: 80, nullable: true)]
    private $region3;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'region4', type: 'string', length: 80, nullable: true)]
    private $region4;

    /**
     * @var string
     */
    #[ORM\Column(name: 'region_language', type: 'string', length: 2, nullable: false)]
    private $regionLanguage;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'iso2', type: 'string', length: 10, nullable: true)]
    private $iso2;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'old_id', type: 'integer', nullable: true)]
    private $oldId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIso(): ?string
    {
        return $this->iso;
    }

    public function setIso(string $iso): self
    {
        $this->iso = $iso;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getNameDirify(): ?string
    {
        return $this->nameDirify;
    }

    public function setNameDirify(string $nameDirify): self
    {
        $this->nameDirify = $nameDirify;

        return $this;
    }

    public function getRegion1(): ?string
    {
        return $this->region1;
    }

    public function setRegion1(?string $region1): self
    {
        $this->region1 = $region1;

        return $this;
    }

    public function getRegion2(): ?string
    {
        return $this->region2;
    }

    public function setRegion2(?string $region2): self
    {
        $this->region2 = $region2;

        return $this;
    }

    public function getRegion3(): ?string
    {
        return $this->region3;
    }

    public function setRegion3(?string $region3): self
    {
        $this->region3 = $region3;

        return $this;
    }

    public function getRegion4(): ?string
    {
        return $this->region4;
    }

    public function setRegion4(?string $region4): self
    {
        $this->region4 = $region4;

        return $this;
    }

    public function getRegionLanguage(): ?string
    {
        return $this->regionLanguage;
    }

    public function setRegionLanguage(string $regionLanguage): self
    {
        $this->regionLanguage = $regionLanguage;

        return $this;
    }

    public function getIso2(): ?string
    {
        return $this->iso2;
    }

    public function setIso2(?string $iso2): self
    {
        $this->iso2 = $iso2;

        return $this;
    }

    public function getOldId(): ?int
    {
        return $this->oldId;
    }

    public function setOldId(?int $oldId): self
    {
        $this->oldId = $oldId;

        return $this;
    }


}
