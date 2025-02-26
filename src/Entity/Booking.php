<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Booking
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de réservation est requise")]
    #[Assert\GreaterThan('now', message: "La date de réservation doit être dans le futur")]
    private ?\DateTimeInterface $bookingDate = null;

    #[ORM\Column(length: 100)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_CANCELLED])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est requis")]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $clientName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "L'email n'est pas valide")]
    private ?string $clientEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: "/^[0-9\-\+\s()]+$/", message: "Numéro de téléphone invalide")]
    private ?string $clientPhone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'booking', targetEntity: Discussion::class, cascade: ['persist', 'remove'])]
    private ?Discussion $discussion = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookingDate(): ?\DateTimeInterface
    {
        return $this->bookingDate;
    }

    public function setBookingDate(?\DateTimeInterface $bookingDate): static
    {
        $this->bookingDate = $bookingDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): static
    {
        $this->clientName = $clientName;
        return $this;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;
        return $this;
    }

    public function getClientPhone(): ?string
    {
        return $this->clientPhone;
    }

    public function setClientPhone(?string $clientPhone): static
    {
        $this->clientPhone = $clientPhone;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getDiscussion(): ?Discussion
    {
        return $this->discussion;
    }

    public function setDiscussion(?Discussion $discussion): static
    {
        // unset the owning side of the relation if necessary
        if ($discussion === null && $this->discussion !== null) {
            $this->discussion->setBooking(null);
        }

        // set the owning side of the relation if necessary
        if ($discussion !== null && $discussion->getBooking() !== $this) {
            $discussion->setBooking($this);
        }

        $this->discussion = $discussion;
        return $this;
    }
}
