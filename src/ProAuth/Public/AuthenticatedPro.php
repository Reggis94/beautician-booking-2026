<?php

namespace App\ProAuth\Public;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The beautician (pro) authenticated by a valid session cookie.
 *
 * Controllers of every domain receive it with #[CurrentUser] and must take the tenant pro ID from it,
 * never from client-provided identifiers. See ADR 0019.
 */
final class AuthenticatedPro implements UserInterface
{
    public const ROLE = 'ROLE_PRO';

    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $linkSlug,
        private readonly ?string $displayName,
        private readonly ?string $firstname,
        private readonly ?string $lastname
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLinkSlug(): string
    {
        return $this->linkSlug;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return [self::ROLE];
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
