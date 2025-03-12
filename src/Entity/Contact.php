<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contact
{
    public const TYPE_PARTICULIER = 'particulier';
    public const TYPE_PROFESSIONNEL = 'professionnel';
    
    public const CIVILITE_MONSIEUR = 'monsieur';
    public const CIVILITE_MADAME = 'madame';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un type de contact')]
    #[Assert\Choice(choices: [self::TYPE_PARTICULIER, self::TYPE_PROFESSIONNEL], message: 'Type de contact invalide')]
    private ?string $type = self::TYPE_PARTICULIER;
    
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner une civilité')]
    #[Assert\Choice(choices: [self::CIVILITE_MONSIEUR, self::CIVILITE_MADAME], message: 'Civilité invalide')]
    private ?string $civilite = null;
    
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Veuillez saisir votre nom')]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $nom = null;
    
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Veuillez saisir votre prénom')]
    #[Assert\Length(max: 50, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $prenom = null;
    
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Veuillez saisir votre adresse email')]
    #[Assert\Email(message: 'L\'adresse email {{ value }} n\'est pas valide')]
    #[Assert\Length(max: 100, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères')]
    private ?string $email = null;
    
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères')]
    private ?string $telephone = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Le nom de l\'entreprise ne peut pas dépasser {{ limit }} caractères')]
    private ?string $entreprise = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'La localisation ne peut pas dépasser {{ limit }} caractères')]
    private ?string $localisation = null;
    
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Veuillez décrire votre projet ou besoin')]
    private ?string $description = null;
    
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;
    
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;
    
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;
    
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getType(): ?string
    {
        return $this->type;
    }
    
    public function setType(string $type): static
    {
        $this->type = $type;
        
        return $this;
    }
    
    public function isParticulier(): bool
    {
        return $this->type === self::TYPE_PARTICULIER;
    }
    
    public function isProfessionnel(): bool
    {
        return $this->type === self::TYPE_PROFESSIONNEL;
    }
    
    public function getCivilite(): ?string
    {
        return $this->civilite;
    }
    
    public function setCivilite(string $civilite): static
    {
        $this->civilite = $civilite;
        
        return $this;
    }
    
    public function getNom(): ?string
    {
        return $this->nom;
    }
    
    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        
        return $this;
    }
    
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }
    
    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        
        return $this;
    }
    
    public function getNomComplet(): string
    {
        return $this->civilite === self::CIVILITE_MONSIEUR ? 'M. ' : 'Mme ' . $this->nom . ' ' . $this->prenom;
    }
    
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    public function setEmail(string $email): static
    {
        $this->email = $email;
        
        return $this;
    }
    
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }
    
    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        
        return $this;
    }
    
    public function getEntreprise(): ?string
    {
        return $this->entreprise;
    }
    
    public function setEntreprise(?string $entreprise): static
    {
        $this->entreprise = $entreprise;
        
        return $this;
    }
    
    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }
    
    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;
        
        return $this;
    }
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(string $description): static
    {
        $this->description = $description;
        
        return $this;
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        
        return $this;
    }
    
    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }
    
    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        
        return $this;
    }
    
    public function markAsRead(): static
    {
        if ($this->readAt === null) {
            $this->readAt = new \DateTimeImmutable();
        }
        
        return $this;
    }
    
    public function isRead(): bool
    {
        return $this->readAt !== null;
    }
    
    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }
    
    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;
        
        return $this;
    }
    
    public function markAsResponded(): static
    {
        if ($this->respondedAt === null) {
            $this->respondedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }
    
    public function isResponded(): bool
    {
        return $this->respondedAt !== null;
    }
}
