<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $stripe_payment_id = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?float $deposit_amount = null;

    #[ORM\Column(length: 255)]
    private ?string $payment_status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $payment_date = null;

    #[ORM\Column]
    private ?bool $validation_status = null;

    #[ORM\OneToOne(inversedBy: 'payment', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripePaymentId(): ?int
    {
        return $this->stripe_payment_id;
    }

    public function setStripePaymentId(int $stripe_payment_id): static
    {
        $this->stripe_payment_id = $stripe_payment_id;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDepositAmount(): ?float
    {
        return $this->deposit_amount;
    }

    public function setDepositAmount(float $deposit_amount): static
    {
        $this->deposit_amount = $deposit_amount;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->payment_status;
    }

    public function setPaymentStatus(string $payment_status): static
    {
        $this->payment_status = $payment_status;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->payment_date;
    }

    public function setPaymentDate(\DateTimeInterface $payment_date): static
    {
        $this->payment_date = $payment_date;

        return $this;
    }

    public function isValidationStatus(): ?bool
    {
        return $this->validation_status;
    }

    public function setValidationStatus(bool $validation_status): static
    {
        $this->validation_status = $validation_status;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }
}
