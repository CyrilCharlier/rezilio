<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
class Campaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable  $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable  $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'campaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Referential $referential = null;

    #[ORM\ManyToOne(inversedBy: 'campaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Society $society = null;

    /**
     * @var Collection<int, MeasureReview>
     */
    #[ORM\OneToMany(targetEntity: MeasureReview::class, mappedBy: 'campaign')]
    private Collection $measureReviews;

    public function __construct()
    {
        $this->measureReviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable  $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getReferential(): ?Referential
    {
        return $this->referential;
    }

    public function setReferential(?Referential $referential): static
    {
        $this->referential = $referential;

        return $this;
    }

    public function getSociety(): ?Society
    {
        return $this->society;
    }

    public function setSociety(?Society $society): static
    {
        $this->society = $society;

        return $this;
    }

    /**
     * @return Collection<int, MeasureReview>
     */
    public function getMeasureReviews(): Collection
    {
        return $this->measureReviews;
    }

    public function addMeasureReview(MeasureReview $measureReview): static
    {
        if (!$this->measureReviews->contains($measureReview)) {
            $this->measureReviews->add($measureReview);
            $measureReview->setCampaign($this);
        }

        return $this;
    }

    public function removeMeasureReview(MeasureReview $measureReview): static
    {
        if ($this->measureReviews->removeElement($measureReview)) {
            // set the owning side to null (unless already changed)
            if ($measureReview->getCampaign() === $this) {
                $measureReview->setCampaign(null);
            }
        }

        return $this;
    }
}
