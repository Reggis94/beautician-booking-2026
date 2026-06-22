<?php

namespace App\Services\Application\Service\QueryHandler;

use App\Services\Application\Service\Dao\ServiceDaoInterface;
use App\Services\Application\Service\Query\ListProPresentationServiceForCategoryQuery as Query;

final class ListProPresentationServiceForCategoryQueryHandler
{
    public function __construct(
        private ServiceDaoInterface $dao
    ) {
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     price_cents: ?int,
     *     duration_min: ?int
     * }>
     */
    public function __invoke(Query $query): array
    {
        // Later, pass the presented pro ID and verify the category and services belong to that pro.
        // See docs/future-improvements/validate-pro-ownership-for-service-presentation-read.md
        return $this->dao->listProPresentationServiceForCategory($query->id);
    }
}
