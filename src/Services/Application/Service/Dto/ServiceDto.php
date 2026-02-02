<?php

namespace App\Services\Application\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ServiceDto
{
    #[Assert\NotBlank(message: 'Service name cannot be empty.', normalizer: 'trim')]
    #[Assert\Length(max: 50, maxMessage: 'Service name must be at most 50 characters.', normalizer: 'trim')]
    public readonly string $name;

    #[Assert\Positive(message: 'Category id must be positive.')]
    public readonly ?int $categoryId;

    #[Assert\Positive(message: 'Professional id must be positive.')]
    public readonly int $proId;

    #[Assert\Length(max: 500, maxMessage: 'Description must be at most 500 characters.', normalizer: 'trim')]
    public readonly ?string $description;

    #[Assert\Positive(message: 'Duration must be positive.')]
    public readonly ?int $durationMin;

    #[Assert\PositiveOrZero(message: 'Price must be zero or positive.')]
    public readonly ?int $priceCents;

    public function __construct(
        string $name,
        ?int $categoryId,
        int $proId,
        ?string $description,
        ?int $durationMin,
        ?int $priceCents
    ) {
        $trimmedDescription = $description !== null ? trim($description) : null;

        $this->name = trim($name);
        $this->categoryId = $categoryId;
        $this->proId = $proId;
        $this->description = $trimmedDescription !== '' ? $trimmedDescription : null;
        $this->durationMin = $durationMin;
        $this->priceCents = $priceCents;
    }
}
