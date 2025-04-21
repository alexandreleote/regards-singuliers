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

    #[ORM\Column(length: 255)]
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
        // Hash l'IP avec BCRYPT 
        $this->ip = password_hash($ip, PASSWORD_BCRYPT, [
            'cost' => 13 // Niveau de coût recommandé pour BCRYPT
        ]);
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
        // Parse le User-Agent pour extraire les informations pertinentes
        if ($userAgent) {
            $parsed = [];
            
            // Détection du navigateur principal
            if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera)[\/ ]([\d.]+)/', $userAgent, $matches)) {
                $parsed[] = $matches[1] . ' ' . $matches[2];
            }
            
            // Détection du système d'exploitation
            if (preg_match('/(Windows NT|Mac OS X|Linux|Android|iOS)[\/ ]?([\d._]+)?/', $userAgent, $matches)) {
                $parsed[] = $matches[1] . ($matches[2] ?? '');
            }
            
            // Détection si c'est un mobile
            if (preg_match('/(Mobile|iPhone|iPad|Android)/', $userAgent)) {
                $parsed[] = 'Mobile';
            }
            
            $this->userAgent = implode(' | ', $parsed);
        } else {
            $this->userAgent = null;
        }
        
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