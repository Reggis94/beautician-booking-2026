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
 * Public list of the categories of a not deleted professional that contain at least one published, not deleted service,
 * in French alphabetical order. services_count counts those published services.
 *
 * Query parameters:
 * - id: required professional id.
 *
 * Successful response: list<{ id: int, name: string, services_count: int }>
 */
#[Route('api/pro-presentation/category/list', name: 'category_pro_presentation', methods: ['GET'])]
final class ListProPresentationPage
{
    public function __invoke(
        ServiceCategoryListQueryHandler $serviceCategoryListQueryHandler,
        #[MapQueryString] ServiceCategoryListQuery $serviceCategoryListQuery
    ): JsonResponse {
        return new JsonResponse($serviceCategoryListQueryHandler($serviceCategoryListQuery));
    }
}
