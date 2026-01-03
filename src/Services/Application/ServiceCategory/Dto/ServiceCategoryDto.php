<?php

namespace App\Services\Application\ServiceCategory\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ServiceCategoryDto
{
    #[Assert\NotBlank(message: 'Category name cannot be empty.', normalizer: 'trim')]
    #[Assert\Length(max: 100, maxMessage: 'Category name must be at most 100 characters.', normalizer: 'trim')]
    public readonly string $name;

    #[Assert\Positive(message: 'Professional id must be positive.')]
    public readonly int $proId;

    public function __construct(string $name, int $proId)
    {
        $this->name = trim($name);
        $this->proId = $proId;
    }
}
