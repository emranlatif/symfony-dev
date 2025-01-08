<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private $password;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private $firstname;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private $surname;

    #[ORM\Column(type: 'date', nullable: true)]
    private $birthdate;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private $googleId;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private $facebookId;

    #[ORM\Column(type: 'boolean')]
    private $enabled;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private $validationHash;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'user')]
    private $transactions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Company", mappedBy="user")
     */
    private Collection $companies;

    /**
     * @return Collection|Company[]
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    #[ORM\OneToMany(targetEntity: Eventadvert::class, mappedBy: 'user')]
    private Collection $eventAdverts;

    /**
     * @return Collection<int, Eventadvert>
     */
    public function getEventAdverts(): Collection
    {
        return $this->eventAdverts;
    }

    public function addEventAdvert(Eventadvert $eventAdvert): self
    {
        if (!$this->eventAdverts->contains($eventAdvert)) {
            $this->eventAdverts[] = $eventAdvert;
            $eventAdvert->setUser($this);
        }

        return $this;
    }

    public function removeEventAdvert(Eventadvert $eventAdvert): self
    {
        if ($this->eventAdverts->removeElement($eventAdvert)) {
            // set the owning side to null (unless already changed)
            if ($eventAdvert->getUser() === $this) {
                $eventAdvert->setUser(null);
            }
        }

        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mollieCustomerId;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    private $credits;

    #[ORM\OneToMany(targetEntity: CreditPayment::class, mappedBy: 'user')]
    private $creditPayments;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $deleted;

    #[ORM\Column(type: 'text')]
    private $remarks;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $blocked;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['default' => 0])]
    private $apStatus;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $deletedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $lastLogin;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['default' => 1])]
    private $sendNotifications;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $allowUnlimitedFreeAdverts;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->creditPayments = new ArrayCollection();
        $this->companies = new ArrayCollection();
        $this->eventAdverts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string)$this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->id;
    }

    public function getBirthdate(): ?DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(string $facebookId): self
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getValidationHash(): ?string
    {
        return $this->validationHash;
    }

    public function setValidationHash(string $validationHash): self
    {
        $this->validationHash = $validationHash;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setUser($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getUser() === $this) {
                $transaction->setUser(null);
            }
        }

        return $this;
    }

    public function getMollieCustomerId(): ?string
    {
        return $this->mollieCustomerId;
    }

    public function setMollieCustomerId(?string $mollieCustomerId): self
    {
        $this->mollieCustomerId = $mollieCustomerId;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): self
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @return Collection<int, CreditPayment>
     */
    public function getCreditPayments(): Collection
    {
        return $this->creditPayments;
    }

    public function addCreditPayment(CreditPayment $creditPayment): self
    {
        if (!$this->creditPayments->contains($creditPayment)) {
            $this->creditPayments[] = $creditPayment;
            $creditPayment->setUser($this);
        }

        return $this;
    }

    public function removeCreditPayment(CreditPayment $creditPayment): self
    {
        if ($this->creditPayments->removeElement($creditPayment)) {
            // set the owning side to null (unless already changed)
            if ($creditPayment->getUser() === $this) {
                $creditPayment->setUser(null);
            }
        }

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getBlocked(): ?bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @param mixed $remarks
     * @return User
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApStatus()
    {
        return $this->apStatus;
    }

    /**
     * @param mixed $apStatus
     * @return User
     */
    public function setApStatus($apStatus)
    {
        $this->apStatus = $apStatus;
        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(DateTimeInterface $deletedAt = null): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getLastLogin(): ?DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSendNotifications()
    {
        return $this->sendNotifications;
    }

    /**
     * @param mixed $sendNotifications
     * @return User
     */
    public function setSendNotifications($sendNotifications)
    {
        $this->sendNotifications = $sendNotifications;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getAllowUnlimitedFreeAdverts(): ?bool
    {
        return $this->allowUnlimitedFreeAdverts;
    }

    public function setAllowUnlimitedFreeAdverts(?bool $allowUnlimitedFreeAdverts): static
    {
        $this->allowUnlimitedFreeAdverts = $allowUnlimitedFreeAdverts;
        return $this;
    }
}
