<?php
// src/Entity/UserSociety.php

namespace App\Entity;

use App\Enum\SocietyRole;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_society')]
#[ORM\UniqueConstraint(name: 'uniq_user_society', columns: ['user_id', 'society_id'])]
class UserSociety
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userSocieties')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Society::class, inversedBy: 'userSocieties')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Society $society = null;

    #[ORM\Column(type: 'string', enumType: SocietyRole::class)]
    private SocietyRole $role = SocietyRole::MEMBER;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSociety(): ?Society
    {
        return $this->society;
    }

    public function setSociety(?Society $society): self
    {
        $this->society = $society;

        return $this;
    }

    public function getRole(): SocietyRole
    {
        return $this->role;
    }

    public function setRole(SocietyRole $role): self
    {
        $this->role = $role;

        return $this;
    }
}
