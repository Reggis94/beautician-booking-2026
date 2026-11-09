<?php

namespace App\Services\Domain\Entity;

use App\Services\Domain\ValueObject\DeletionReason;

/**
 * One version of a service category.
 *
 * A category has no publication state of its own: it is active when it contains at least one
 * published, not deleted service. Renaming a category referenced by appointments replaces it
 * (see the RenameServiceCategory handler), so appointments keep the name they were booked under.
 */
final class ServiceCategory
{
    public const NAME_MAX_LENGTH = 100;

    private function __construct(
        private ?int $id,
        private readonly int $proId,
        private string $name,
        private ?\DateTimeImmutable $deletedAt,
        private ?DeletionReason $deletionReason,
        private readonly ?int $previousVersionId
    ) {
    }

    public static function create(int $proId, string $name): self
    {
        return new self(null, $proId, self::normalizeName($name), null, null, null);
    }

    public static function fromPersistence(
        int $id,
        int $proId,
        string $name,
        ?\DateTimeImmutable $deletedAt,
        ?DeletionReason $deletionReason,
        ?int $previousVersionId
    ): self {
        return new self($id, $proId, $name, $deletedAt, $deletionReason, $previousVersionId);
    }

    public static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException('A category name must have 1 to 100 characters.');
        }

        return $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function identify(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('A persisted category cannot change its id.');
        }

        $this->id = $id;
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getDeletionReason(): ?DeletionReason
    {
        return $this->deletionReason;
    }

    public function getPreviousVersionId(): ?int
    {
        return $this->previousVersionId;
    }

    /**
     * True when deleted_at is null: neither explicitly deleted nor replaced. It may contain no published service.
     */
    public function isNotDeleted(): bool
    {
        return $this->deletedAt === null;
    }

    public function hasName(string $name): bool
    {
        return $this->name === self::normalizeName($name);
    }

    /**
     * Renames in place. Only valid for a category without appointment history.
     */
    public function rename(string $name): void
    {
        $this->assertNotDeleted();
        $this->name = self::normalizeName($name);
    }

    /**
     * Retires this version and returns its renamed replacement.
     */
    public function replaceWith(string $name, \DateTimeImmutable $now): self
    {
        $this->assertNotDeleted();

        if ($this->id === null) {
            throw new \LogicException('Only a persisted category can be replaced.');
        }

        $replacement = new self(null, $this->proId, self::normalizeName($name), null, null, $this->id);

        $this->deletedAt = $now;
        $this->deletionReason = DeletionReason::Replaced;

        return $replacement;
    }

    public function delete(\DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->deletedAt = $now;
        $this->deletionReason = DeletionReason::Deleted;
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new \LogicException('A deleted or replaced category version cannot change.');
        }
    }
}
