<?php

namespace App\Services\Application\Service\QueryHandler;

use App\Services\Application\Service\Query\ListProPresentationUncategorizedServicesQuery as Query;
use App\Services\Domain\Repository\ServiceRepositoryInterface;

/**
 * Published, not deleted services of the presented professional that have no category, in French alphabetical order.
 * The public page shows them after every category.
 */
final class ListProPresentationUncategorizedServicesQueryHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services
    ) {
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     price_cents: ?int,
     *     duration_min: ?int,
     *     images: list<array{id: int, file_name: string}>
     * }>
     */
    public function __invoke(Query $query): array
    {
        return PresentationServices::withImagesInOrder(
            $this->services,
            $this->services->listPublishedUncategorized($query->id)
        );
    }
}
