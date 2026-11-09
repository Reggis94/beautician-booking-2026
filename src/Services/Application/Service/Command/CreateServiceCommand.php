<?php

namespace App\Services\Application\Service\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the multipart form (or JSON) body of POST /api/pro/service/new.
 * The professional comes from the session and images from the uploaded files, not from this command.
 */
final class CreateServiceCommand
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 50, normalizer: 'trim')]
        public readonly string $name = '',
        #[Assert\Length(max: 500, normalizer: 'trim')]
        public readonly ?string $description = null,
        #[Assert\NotNull]
        #[Assert\Range(min: 5, max: 720)]
        #[Assert\DivisibleBy(5)]
        public readonly ?int $durationMin = null,
        #[Assert\NotNull]
        #[Assert\Range(min: 0, max: 1000000)]
        public readonly ?int $priceCents = null,
        #[Assert\Positive]
        public readonly ?int $categoryId = null,
        public readonly bool $published = false
    ) {
    }
}
