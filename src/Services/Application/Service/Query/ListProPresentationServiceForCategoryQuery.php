<?php

namespace App\Services\Application\Service\Query;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the query string of GET /api/pro-presentation/service/list?id={categoryId}&proId={proId}.
 */
final class ListProPresentationServiceForCategoryQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $id,
        #[Assert\Positive]
        public readonly int $proId,
    ) {
    }
}
