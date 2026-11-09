<?php

namespace App\Services\Domain\Entity;

/**
 * An image of a service, stored under a random file name that is unique across all professionals.
 *
 * Images are not versioned: they belong to the not deleted version of their service and move with it when the
 * service is replaced. Removing an image soft-deletes it.
 */
final class ServiceImage
{
    private const FILE_NAME_PATTERN = '/^[a-f0-9]{32}\.(jpg|png|webp)$/';

    private function __construct(
        private ?int $id,
        private readonly string $fileName,
        private ?\DateTimeImmutable $deletedAt
    ) {
    }

    public static function attach(string $fileName): self
    {
        if (preg_match(self::FILE_NAME_PATTERN, $fileName) !== 1) {
            throw new \InvalidArgumentException('A service image file name must be random and generated.');
        }

        return new self(null, $fileName, null);
    }

    public static function fromPersistence(int $id, string $fileName, ?\DateTimeImmutable $deletedAt): self
    {
        return new self($id, $fileName, $deletedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function identify(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('A persisted service image cannot change its id.');
        }

        $this->id = $id;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function detach(\DateTimeImmutable $now): void
    {
        if ($this->deletedAt === null) {
            $this->deletedAt = $now;
        }
    }
}
