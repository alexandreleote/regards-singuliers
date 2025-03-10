<?php

namespace App\Entity;

use App\Repository\RealisationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: RealisationRepository::class)]
class Realisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $mainImage = null;

    #[ORM\Column(type: Types::JSON)]
    private array $additionalImages = [];

    private ?array $imageFiles = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->additionalImages = [];
        $this->imageFiles = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
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

    public function getMainImage(): ?string
    {
        return $this->mainImage;
    }

    public function setMainImage(?string $mainImage): static
    {
        $this->mainImage = $mainImage;
        return $this;
    }

    public function getAdditionalImages(): array
    {
        return $this->additionalImages;
    }

    public function setAdditionalImages(array $additionalImages): static
    {
        $this->additionalImages = $additionalImages;
        return $this;
    }

    public function addAdditionalImage(string $imagePath): static
    {
        if (!in_array($imagePath, $this->additionalImages)) {
            $this->additionalImages[] = $imagePath;
        }
        return $this;
    }

    public function removeAdditionalImage(string $imagePath): static
    {
        $key = array_search($imagePath, $this->additionalImages);
        if ($key !== false) {
            unset($this->additionalImages[$key]);
            $this->additionalImages = array_values($this->additionalImages);
        }
        return $this;
    }

    public function getImageFiles(): ?array
    {
        return $this->imageFiles;
    }

    public function setImageFiles(?array $imageFiles): static
    {
        $this->imageFiles = $imageFiles;
        return $this;
    }
}