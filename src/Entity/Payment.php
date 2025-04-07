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

    #[ORM\Column(length: 255)]
    private ?string $stripe_payment_id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $depositAmount = null;

    #[ORM\Column(length: 50)]
    private ?string $paymentStatus = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 255)]
    private ?string $validationStatus = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 50)]
    private ?string $billingNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $billingAddress = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $billingDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $canceledAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripePaymentId(): ?string
    {
        return $this->stripe_payment_id;
    }

    public function setStripePaymentId(string $stripe_payment_id): static
    {
        $this->stripe_payment_id = $stripe_payment_id;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getDepositAmount(): ?string
    {
        return $this->depositAmount;
    }

    public function setDepositAmount(string $depositAmount): static
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getValidationStatus(): ?string
    {
        return $this->validationStatus;
    }

    public function setValidationStatus(string $validationStatus): static
    {
        $this->validationStatus = $validationStatus;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getBillingNumber(): ?string
    {
        return $this->billingNumber;
    }

    public function setBillingNumber(string $billingNumber): static
    {
        $this->billingNumber = $billingNumber;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingDate(): ?\DateTimeImmutable
    {
        return $this->billingDate;
    }

    public function setBillingDate(\DateTimeImmutable $billingDate): static
    {
        $this->billingDate = $billingDate;

        return $this;
    }

    public function getCanceledAt(): ?\DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?\DateTimeImmutable $canceledAt): static
    {
        $this->canceledAt = $canceledAt;
        return $this;
    }
}
