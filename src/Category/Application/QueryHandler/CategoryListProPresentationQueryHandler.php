<?php

namespace App\Category\Application\QueryHandler;

use App\Category\Application\Contract\Repository\CategoryRepositoryInterface;
use App\Category\Application\Query\CategoryListProPresentationQuery;

final class CategoryListProPresentationQueryHandler
{
    public function __construct(private CategoryRepositoryInterface $repo)
    {
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function __invoke(
        CategoryListProPresentationQuery $categoryListProPresentationQuery,
    ): array {
        // See docs/future-improvements/validate-pro-exists-for-category-presentation-read.md
        return $this->repo->getListForProPresentation($categoryListProPresentationQuery->id);
    }
}
