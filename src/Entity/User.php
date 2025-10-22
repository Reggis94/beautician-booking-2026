<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'pro')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|string $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;
    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    public function __construct(int|string $id, string $email, array $roles = ['ROLE_USER'])
    {
        $this->id = $id;
        $this->email = strtolower($email);
        $this->setRoles($roles);
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function setId(int|string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower($email);
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_values(array_unique($roles));
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles ?: ['ROLE_USER']));
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If any temporary, sensitive data is stored on the user, clear it here
        // e.g. $this->plainPassword = null;
    }
}
