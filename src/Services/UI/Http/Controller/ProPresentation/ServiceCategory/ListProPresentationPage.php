<?php

namespace App\Services\UI\Http\Controller\ProPresentation\ServiceCategory;

use App\Services\Application\ServiceCategory\Query\ServiceCategoryListProPresentationQuery as ServiceCategoryListQuery;
use App\Services\Application\ServiceCategory\QueryHandler\ServiceCategoryListProPresentationQueryHandler as ServiceCategoryListQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-presentation/category/list?id={proId}
 *
 * Returns the non-deleted service categories for the given professional profile,
 * ordered by category ID ascending.
 *
 * Query parameters:
 * - id: required integer professional profile ID.
 *
 * Successful response: array<int, array{id: int, name: string, services_count: int}>
 */
#[Route('api/pro-presentation/category/list', name: 'category_pro_presentation', methods: ['GET'])]
final class ListProPresentationPage
{
    public function __invoke(
        ServiceCategoryListQueryHandler $serviceCategoryListQueryHandler,
        #[MapQueryString] ServiceCategoryListQuery $serviceCategoryListQuery
    ): JsonResponse {
        $categories = $serviceCategoryListQueryHandler($serviceCategoryListQuery);

        return new JsonResponse($categories);
    }
}
