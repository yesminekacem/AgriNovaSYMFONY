<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: false)]
    private string $email;

    #[ORM\Column(type: 'string', length: 100, options: ['default' => 'USER'])]
    private string $role = 'USER';

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(name: 'email_verified', type: 'boolean', options: ['default' => 0])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 100, nullable: true, name: 'profile_image')]
    private ?string $profileImage = null;


    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $banned = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        // Map legacy single role string to Symfony roles array
        $r = strtoupper($this->role ?? 'USER');
        $roles = ['ROLE_USER'];
        if ($r === 'ADMIN' || $r === 'ROLE_ADMIN') {
            array_unshift($roles, 'ROLE_ADMIN');
        }

        return array_unique($roles);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function setRoles(array $roles): self
    {
        // Keep compatibility: accept an array but store first role in legacy column
        $first = $roles[0] ?? 'ROLE_USER';
        // normalize
        $this->role = str_ireplace('ROLE_', '', $first);

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): self
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function getBanned(): bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned): self
    {
        $this->banned = $banned;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return null;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        // no-op, token is stateless in this build
        return $this;
    }
}
