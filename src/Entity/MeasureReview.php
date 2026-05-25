<?php

namespace App\Entity;

use App\Enum\MeasureReviewStatus;
use App\Repository\MeasureReviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: MeasureReviewRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'measure_review')]
#[ORM\UniqueConstraint(name: 'uniq_campaign_measure_review', columns: ['campaign_id', 'measure_id'])]
#[UniqueEntity(
    fields: ['campaign', 'measure'],
    message: 'Une revue existe déjà pour cette mesure dans cette campagne.'
)]
class MeasureReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: MeasureReviewStatus::class)]
    private MeasureReviewStatus  $status = MeasureReviewStatus::NON_COMPLIANT;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $proofUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $explanation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notApplicableReason = null;

    #[ORM\ManyToOne(inversedBy: 'measureReviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campaign $campaign = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MeasureNode $measure = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Evidence>
     */
    #[ORM\OneToMany(targetEntity: Evidence::class, mappedBy: 'measureReview', orphanRemoval: true)]
    private Collection $evidences;

    /**
     * @var Collection<int, RemediationAction>
     */
    #[ORM\OneToMany(targetEntity: RemediationAction::class, mappedBy: 'measureReview')]
    private Collection $remediationActions;

    public function __construct()
    {
        $this->remediationActions = new ArrayCollection();
        $this->evidences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): MeasureReviewStatus
    {
        return $this->status;
    }

    public function setStatus(MeasureReviewStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getProofUrl(): ?string
    {
        return $this->proofUrl;
    }

    public function setProofUrl(?string $proofUrl): static
    {
        $this->proofUrl = $proofUrl;

        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(?string $explanation): static
    {
        $this->explanation = $explanation;

        return $this;
    }

    public function getNotApplicableReason(): ?string
    {
        return $this->notApplicableReason;
    }

    public function setNotApplicableReason(?string $notApplicableReason): static
    {
        $this->notApplicableReason = $notApplicableReason;

        return $this;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getMeasure(): ?MeasureNode
    {
        return $this->measure;
    }

    public function setMeasure(?MeasureNode $measure): static
    {
        $this->measure = $measure;

        return $this;
    }

    /**
     * @return Collection<int, RemediationAction>
     */
    public function getRemediationActions(): Collection
    {
        return $this->remediationActions;
    }

    public function addRemediationAction(RemediationAction $remediationAction): static
    {
        if (!$this->remediationActions->contains($remediationAction)) {
            $this->remediationActions->add($remediationAction);
            $remediationAction->setMeasureReview($this);
        }

        return $this;
    }

    public function removeRemediationAction(RemediationAction $remediationAction): static
    {
        if ($this->remediationActions->removeElement($remediationAction)) {
            // set the owning side to null (unless already changed)
            if ($remediationAction->getMeasureReview() === $this) {
                $remediationAction->setMeasureReview(null);
            }
        }

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

    /**
     * @return Collection<int, Evidence>
     */
    public function getEvidences(): Collection
    {
        return $this->evidences;
    }

    public function addEvidence(Evidence $evidence): static
    {
        if (!$this->evidences->contains($evidence)) {
            $this->evidences->add($evidence);
            $evidence->setMeasureReview($this);
        }

        return $this;
    }

    public function removeEvidence(Evidence $evidence): static
    {
        if ($this->evidences->removeElement($evidence)) {
            if ($evidence->getMeasureReview() === $this) {
                $evidence->setMeasureReview(null);
            }
        }

        return $this;
    }

    public function getEvidenceCount(): int
    {
        return $this->evidences->count();
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
