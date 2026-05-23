<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var boolean The status of the user
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $isActive = true;

    /**
     * @var DateTimeImmutable The DateTime of the deactivate of the user
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deactivatedAt = null;

    /**
     * @var DateTimeImmutable The DateTime of the last login of the user
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $totpEnabled = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserSociety::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $userSocieties;

    public function __construct()
    {
        $this->userSocieties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive ?? true;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDeactivatedAt(): ?\DateTimeImmutable
    {
        return $this->deactivatedAt;
    }

    public function setDeactivatedAt(?\DateTimeImmutable $deactivatedAt): self
    {
        $this->deactivatedAt = $deactivatedAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpSecret !== null && $this->totpEnabled === true;
    }

    public function getTotpAuthenticationUsername(): string
    {
        // Ce qui apparaît dans l’app TOTP
        return $this->getUserIdentifier();
    }

    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
    {
        return new TotpConfiguration(
            $this->totpSecret,
            TotpConfiguration::ALGORITHM_SHA1,
            30, // période en secondes
            6   // nombre de chiffres
        );
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $secret): self
    {
        $this->totpSecret = $secret;
        return $this;
    }

    public function isTotpEnabled(): bool
    {
        return $this->totpEnabled ?? false;
    }

    public function setTotpEnabled(bool $enabled): self
    {
        $this->totpEnabled = $enabled;
        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
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
            $userSociety->setUser($this);
        }

        return $this;
    }

    public function removeUserSociety(UserSociety $userSociety): self
    {
        if ($this->userSocieties->removeElement($userSociety)) {
            // set the owning side to null (unless already changed)
            if ($userSociety->getUser() === $this) {
                $userSociety->setUser(null);
            }
        }

        return $this;
    }

    public function hasSociety(Society $society): bool
    {
        foreach ($this->userSocieties as $userSociety) {
            if ($userSociety->getSociety() === $society) {
                return true;
            }
        }

        return false;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
