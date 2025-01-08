<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GeoPlaces
 */
#[ORM\Entity(repositoryClass: \App\Repository\GeoPlacesRepository::class)]
#[ORM\Table(name: 'geo_places')]
#[ORM\Index(name: 'id', columns: ['id'])]
#[ORM\Index(name: 'iso', columns: ['iso'])]
#[ORM\Index(name: 'iso_2', columns: ['iso', 'locality', 'postcode'])]
#[ORM\Index(name: 'locality_dirify', columns: ['locality_dirify'])]
class GeoPlaces
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'language', type: 'string', length: 2, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $language;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
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
     * @var int|null
     */
    #[ORM\Column(name: 'old_id', type: 'integer', nullable: true)]
    private $oldId;

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
     * @var string|null
     */
    #[ORM\Column(name: 'locality', type: 'string', length: 80, nullable: true)]
    private $locality;

    /**
     * @var string
     */
    #[ORM\Column(name: 'locality_dirify', type: 'string', length: 80, nullable: false)]
    private $localityDirify;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'postcode', type: 'string', length: 15, nullable: true)]
    private $postcode;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'suburb', type: 'string', length: 80, nullable: true)]
    private $suburb;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'latitude', type: 'float', precision: 10, scale: 0, nullable: true)]
    private $latitude;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'longitude', type: 'float', precision: 10, scale: 0, nullable: true)]
    private $longitude;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'iso2', type: 'string', length: 10, nullable: true)]
    private $iso2;

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getId(): ?string
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

    public function getOldId(): ?int
    {
        return $this->oldId;
    }

    public function setOldId(?int $oldId): self
    {
        $this->oldId = $oldId;

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

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(?string $locality): self
    {
        $this->locality = $locality;

        return $this;
    }

    public function getLocalityDirify(): ?string
    {
        return $this->localityDirify;
    }

    public function setLocalityDirify(string $localityDirify): self
    {
        $this->localityDirify = $localityDirify;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getSuburb(): ?string
    {
        return $this->suburb;
    }

    public function setSuburb(?string $suburb): self
    {
        $this->suburb = $suburb;

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

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

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


}
