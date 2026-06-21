<?php

namespace App\Services\UI\Http\Controller\ProPresentation\Service;

use App\Services\Application\Service\Query\ListProPresentationServiceForCategoryQuery as Query;
use App\Services\Application\Service\QueryHandler\ListProPresentationServiceForCategoryQueryHandler as Handler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-presentation/service/list?id={categoryId}
 *
 * Returns the non-deleted services for the given service category,
 * ordered by service ID ascending.
 *
 * Query parameters:
 * - id: required integer service category ID.
 *
 * Successful response: array<int, array{name: string, description: ?string, price_cents: ?int, duration_min: ?int}>
 */
#[Route(
    'api/pro-presentation/service/list',
    name: 'service_list_per_category',
    methods: ['GET']
)]
final class ListProPresentationServiceForCategory
{
    public function __invoke(
        #[MapQueryString] Query $query,
        Handler $handler
    ): JsonResponse {
        return new JsonResponse($handler($query));
    }
}
