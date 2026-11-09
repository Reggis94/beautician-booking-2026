<?php

namespace App\Services\Application\Service\QueryHandler;

use App\Services\Application\Service\Query\ListProPresentationServiceForCategoryQuery as Query;
use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\Service\FrenchNameOrder;

/**
 * Published, not deleted services of one category of the presented professional, in French alphabetical order.
 * Both the category and the services must belong to that professional.
 */
final class ListProPresentationServiceForCategoryQueryHandler
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
            $this->services->listPublishedInCategory($query->proId, $query->id)
        );
    }
}
