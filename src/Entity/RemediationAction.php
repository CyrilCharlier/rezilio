<?php

namespace App\Entity;

use App\Enum\RemediationActionStatus;
use App\Enum\RemediationActionPriority;
use App\Repository\RemediationActionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RemediationActionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RemediationAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: RemediationActionPriority::class)]
    private ?RemediationActionPriority $priority = RemediationActionPriority::MEDIUM;

    #[ORM\Column(type: 'string', enumType: RemediationActionStatus::class)]
    private ?RemediationActionStatus $status = RemediationActionStatus::DRAFT;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dueDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'remediationActions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MeasureReview $measureReview = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): ?RemediationActionPriority
    {
        return $this->priority;
    }

    public function setPriority(RemediationActionPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): ?RemediationActionStatus
    {
        return $this->status;
    }

    public function setStatus(RemediationActionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTime $dueDate): static
    {
        $this->dueDate = $dueDate;

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
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
