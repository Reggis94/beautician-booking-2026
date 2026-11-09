<?php

namespace App\Services\Application\ServiceCategory\Query;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the query string of GET /api/pro-presentation/category/list?id={proId}.
 */
final class ServiceCategoryListProPresentationQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $id,
    ) {
    }
}
