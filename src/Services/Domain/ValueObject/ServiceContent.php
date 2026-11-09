<?php

namespace App\Services\Domain\ValueObject;

/**
 * Historical content of a service version: what an appointment was booked for.
 *
 * Changing any of these values on a service with appointment history creates a new version
 * (see ServiceNewVersionDecision). Publication and images are not part of the content.
 */
final class ServiceContent
{
    public const NAME_MAX_LENGTH = 50;
    public const DESCRIPTION_MAX_LENGTH = 500;

    private readonly string $name;
    private readonly ?string $description;

    public function __construct(
        string $name,
        ?string $description,
        private readonly ?int $durationMin,
        private readonly ?int $priceCents,
        private readonly ?int $categoryId
    ) {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException('A service name must have 1 to 50 characters.');
        }

        $description = $description !== null ? trim($description) : null;
        if ($description !== null && mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH) {
            throw new \InvalidArgumentException('A service description must have at most 500 characters.');
        }

        if ($durationMin !== null && $durationMin <= 0) {
            throw new \InvalidArgumentException('A service duration must be positive.');
        }

        if ($priceCents !== null && $priceCents < 0) {
            throw new \InvalidArgumentException('A service price cannot be negative.');
        }

        if ($categoryId !== null && $categoryId <= 0) {
            throw new \InvalidArgumentException('A category id must be positive.');
        }

        $this->name = $name;
        $this->description = $description === '' ? null : $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDurationMin(): ?int
    {
        return $this->durationMin;
    }

    public function getPriceCents(): ?int
    {
        return $this->priceCents;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function withCategoryId(?int $categoryId): self
    {
        return new self($this->name, $this->description, $this->durationMin, $this->priceCents, $categoryId);
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->description === $other->description
            && $this->durationMin === $other->durationMin
            && $this->priceCents === $other->priceCents
            && $this->categoryId === $other->categoryId;
    }
}
