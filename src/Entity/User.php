<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private ?bool $isBanned = null;

    #[ORM\Column]
    private ?bool $isTempBanned = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $streetNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $streetName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $zip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region = null;

    /**
     * Vérifie si l'utilisateur est vérifié.
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Conversation::class)]
    private Collection $conversations;

    /**
     * Constructeur de l'entité User.
     */
    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->conversations = new ArrayCollection();
    }

    /**
     * Récupère l'identifiant de l'utilisateur.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère l'adresse e-mail de l'utilisateur.
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'adresse e-mail de l'utilisateur.
     *
     * @param string $email Adresse e-mail
     * @return static
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Définit les rôles de l'utilisateur.
     *
     * @param list<string> $roles Rôles
     * @return static
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Définit le mot de passe de l'utilisateur.
     *
     * @param string $password Mot de passe
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Récupère le nom de l'utilisateur.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Définit le nom de l'utilisateur.
     *
     * @param string|null $name Nom
     * @return static
     */
    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Récupère le prénom de l'utilisateur.
     *
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * Définit le prénom de l'utilisateur.
     *
     * @param string|null $firstname Prénom
     * @return static
     */
    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Récupère le numéro de téléphone de l'utilisateur.
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Définit le numéro de téléphone de l'utilisateur.
     *
     * @param string|null $phone Numéro de téléphone
     * @return static
     */
    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur est banni.
     *
     * @return bool|null
     */
    public function isBanned(): ?bool
    {
        return $this->isBanned;
    }

    /**
     * Définit le statut de bannissement de l'utilisateur.
     *
     * @param bool $isBanned Statut de bannissement
     * @return static
     */
    public function setIsBanned(bool $isBanned): static
    {
        $this->isBanned = $isBanned;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur est temporairement banni.
     *
     * @return bool|null
     */
    public function isTempBanned(): ?bool
    {
        return $this->isTempBanned;
    }

    /**
     * Définit le statut de bannissement temporaire de l'utilisateur.
     *
     * @param bool $isTempBanned Statut de bannissement temporaire
     * @return static
     */
    public function setIsTempBanned(bool $isTempBanned): static
    {
        $this->isTempBanned = $isTempBanned;

        return $this;
    }

    /**
     * Récupère le numéro de rue de l'utilisateur.
     *
     * @return string|null
     */
    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    /**
     * Définit le numéro de rue de l'utilisateur.
     *
     * @param string|null $streetNumber Numéro de rue
     * @return static
     */
    public function setStreetNumber(?string $streetNumber): static
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * Récupère le nom de la rue de l'utilisateur.
     *
     * @return string|null
     */
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    /**
     * Définit le nom de la rue de l'utilisateur.
     *
     * @param string|null $streetName Nom de la rue
     * @return static
     */
    public function setStreetName(?string $streetName): static
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * Récupère la ville de l'utilisateur.
     *
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Définit la ville de l'utilisateur.
     *
     * @param string|null $city Ville
     * @return static
     */
    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Récupère le code postal de l'utilisateur.
     *
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * Définit le code postal de l'utilisateur.
     *
     * @param string|null $zip Code postal
     * @return static
     */
    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Récupère la région de l'utilisateur.
     *
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * Définit la région de l'utilisateur.
     *
     * @param string|null $region Région
     * @return static
     */
    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur est vérifié.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    /**
     * Définit le statut de vérification de l'utilisateur.
     *
     * @param bool $isVerified Statut de vérification
     * @return static
     */
    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * Récupère les réservations de l'utilisateur.
     *
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    /**
     * Récupère les conversations de l'utilisateur.
     *
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }
}
