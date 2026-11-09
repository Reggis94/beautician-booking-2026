<?php

namespace App\Services\Application\Service\Query;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the query string of GET /api/pro-presentation/service/uncategorized/list?id={proId}.
 */
final class ListProPresentationUncategorizedServicesQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $id,
    ) {
    }
}
