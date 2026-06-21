<?php

namespace App\Category\UI\Http\Controller;

use App\Category\Application\Query\CategoryListProPresentationQuery as CategoryListQuery;
use App\Category\Application\QueryHandler\CategoryListProPresentationQueryHandler as CategoryListQueryHandler;
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
 * Successful response: array<int, array{id: int, name: string}>
 */
#[Route('api/pro-presentation/category/list', name: 'category_pro_presentation', methods: ['GET'])]
final class ListProPresentationPage
{
    public function __invoke(
        CategoryListQueryHandler $categoryListQueryHandler,
        #[MapQueryString] CategoryListQuery $categoryListQuery
    ): JsonResponse {
        $categories = $categoryListQueryHandler($categoryListQuery);

        return new JsonResponse($categories);
    }
}
