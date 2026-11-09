<?php

namespace App\Services\Application\ServiceCategory\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the JSON body of POST /api/pro/service/category/new.
 */
final class CreateServiceCategoryCommand
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 100, normalizer: 'trim')]
        public readonly string $name = ''
    ) {
    }
}
