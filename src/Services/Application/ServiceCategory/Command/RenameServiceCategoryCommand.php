<?php

namespace App\Services\Application\ServiceCategory\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the JSON body of PUT /api/pro/service/category/edit.
 */
final class RenameServiceCategoryCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly ?int $serviceCategoryId = null,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 100, normalizer: 'trim')]
        public readonly string $name = ''
    ) {
    }
}
