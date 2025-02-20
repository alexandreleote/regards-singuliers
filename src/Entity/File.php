<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: FileRepository::class)]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column]
    private ?int $fileSize = null;

    #[ORM\Column(length: 100)]
    private ?string $fileType = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $uploadDate = null;

    #[ORM\Column]
    private ?bool $isValid = null;

    #[ORM\Column]
    private ?int $maxAllowedSize = null;

    #[ORM\Column(length: 255)]
    private ?string $allowedExtension = null;

    #[ORM\ManyToMany(targetEntity: Message::class, mappedBy: 'files')]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): static
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function getUploadDate(): ?\DateTimeImmutable
    {
        return $this->uploadDate;
    }

    public function setUploadDate(\DateTimeImmutable $uploadDate): static
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    public function isValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): static
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getMaxAllowedSize(): ?int
    {
        return $this->maxAllowedSize;
    }

    public function setMaxAllowedSize(int $maxAllowedSize): static
    {
        $this->maxAllowedSize = $maxAllowedSize;

        return $this;
    }

    public function getAllowedExtension(): ?string
    {
        return $this->allowedExtension;
    }

    public function setAllowedExtension(string $allowedExtension): static
    {
        $this->allowedExtension = $allowedExtension;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->addFile($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            $message->removeFile($this);
        }
        return $this;
    }
}
