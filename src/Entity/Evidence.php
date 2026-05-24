<?php

namespace App\Entity;

use App\Repository\EvidenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: EvidenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'evidence')]
#[Vich\Uploadable]
class Evidence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        mimeTypesMessage: 'Formats autorisés : PDF, PNG, JPG, DOC, DOCX.'
    )]
    #[Vich\UploadableField(mapping: 'evidence_file', fileNameProperty: 'storedFilename', size: 'fileSize')]
    private ?File $file = null;

    #[ORM\Column(length: 255)]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 255)]
    private ?string $storedFilename = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $fileSize = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'evidences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MeasureReview $measureReview = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;

        if ($file instanceof UploadedFile) {
            $this->originalFilename = $file->getClientOriginalName();
            $this->mimeType = $file->getClientMimeType() ?? $file->getMimeType() ?? 'application/octet-stream';
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getStoredFilename(): ?string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(?string $storedFilename): static
    {
        $this->storedFilename = $storedFilename;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMeasureReview(): ?MeasureReview
    {
        return $this->measureReview;
    }

    public function setMeasureReview(?MeasureReview $measureReview): static
    {
        $this->measureReview = $measureReview;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->fileSize ?? 0;

        if ($bytes < 1024) {
            return $bytes . ' o';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' Ko';
        }

        return round($bytes / 1048576, 1) . ' Mo';
    }

    public function getFileCategory(): string
    {
        $mimeType = $this->mimeType ?? '';

        return match (true) {
            $mimeType === 'application/pdf' => 'pdf',
            str_starts_with($mimeType, 'image/') => 'image',
            in_array($mimeType, [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ], true) => 'document',
            default => 'other',
        };
    }
}
