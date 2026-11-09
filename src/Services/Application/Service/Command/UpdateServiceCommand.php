<?php

namespace App\Services\Application\Service\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the multipart form (or JSON) body of POST /api/pro/service/edit.
 *
 * The whole form is sent: content, publication, the ids of the current images to keep,
 * and new image files (not part of this command).
 */
final class UpdateServiceCommand
{
    /**
     * @param list<int> $keptImageIds Active image ids of the edited version to keep; the others are removed.
     */
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly ?int $serviceId = null,
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
        public readonly bool $published = false,
        #[Assert\Count(max: 2)]
        #[Assert\All([new Assert\Positive()])]
        public readonly array $keptImageIds = []
    ) {
    }

    /**
     * @return list<int>
     */
    public function getKeptImageIds(): array
    {
        $ids = [];
        foreach ($this->keptImageIds as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }
}
