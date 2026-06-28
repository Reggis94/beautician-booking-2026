<?php

namespace App\Services\Application\Service\QueryHandler;

use App\Services\Application\Service\Dao\ServiceDaoInterface;
use App\Services\Application\Service\Query\ListProAdminServicesQuery;

final class ListProAdminServicesQueryHandler
{
    public function __construct(
        private readonly ServiceDaoInterface $dao
    ) {
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     duration_minutes: ?int,
     *     price_cents: ?int,
     *     currency: string
     * }>
     */
    public function __invoke(ListProAdminServicesQuery $query): array
    {
        return $this->dao->listProAdminServices($query->proId);
    }
}
