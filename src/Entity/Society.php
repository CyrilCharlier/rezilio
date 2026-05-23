<?php

namespace App\Entity;

use App\Repository\SocietyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocietyRepository::class)]
class Society
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @var Collection<int, Campaign>
     */
    #[ORM\OneToMany(targetEntity: Campaign::class, mappedBy: 'society')]
    private Collection $campaigns;

    #[ORM\OneToMany(mappedBy: 'society', targetEntity: UserSociety::class, orphanRemoval: true)]
    private Collection $userSocieties;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
        $this->userSocieties = new ArrayCollection();
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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChildren(self $children): static
    {
        if (!$this->children->contains($children)) {
            $this->children->add($children);
            $children->setParent($this);
        }

        return $this;
    }

    public function removeChildren(self $children): static
    {
        if ($this->children->removeElement($children)) {
            // set the owning side to null (unless already changed)
            if ($children->getParent() === $this) {
                $children->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): static
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns->add($campaign);
            $campaign->setSociety($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): static
    {
        if ($this->campaigns->removeElement($campaign)) {
            // set the owning side to null (unless already changed)
            if ($campaign->getSociety() === $this) {
                $campaign->setSociety(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserSociety>
     */
    public function getUserSocieties(): Collection
    {
        return $this->userSocieties;
    }

    public function addUserSociety(UserSociety $userSociety): self
    {
        if (!$this->userSocieties->contains($userSociety)) {
            $this->userSocieties->add($userSociety);
            $userSociety->setSociety($this);
        }

        return $this;
    }

    public function removeUserSociety(UserSociety $userSociety): self
    {
        if ($this->userSocieties->removeElement($userSociety)) {
            if ($userSociety->getSociety() === $this) {
                $userSociety->setSociety(null);
            }
        }

        return $this;
    }
}
