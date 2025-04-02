<?php

namespace App\Entity;

use App\Repository\BotIpRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BotIpRepository::class)]
class BotIp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $ip = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $detectedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formType = null;

    public function __construct()
    {
        $this->detectedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;
        return $this;
    }

    public function getDetectedAt(): ?\DateTimeImmutable
    {
        return $this->detectedAt;
    }

    public function setDetectedAt(\DateTimeImmutable $detectedAt): static
    {
        $this->detectedAt = $detectedAt;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function setFormType(?string $formType): static
    {
        $this->formType = $formType;
        return $this;
    }
} 