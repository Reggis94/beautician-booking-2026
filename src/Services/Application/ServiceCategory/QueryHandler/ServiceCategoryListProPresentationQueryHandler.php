<?php

namespace App\Services\Application\ServiceCategory\QueryHandler;

use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;
use App\Services\Application\ServiceCategory\Query\ServiceCategoryListProPresentationQuery;

final class ServiceCategoryListProPresentationQueryHandler
{
    public function __construct(private ServiceCategoryDaoInterface $serviceCategoryDao)
    {
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function __invoke(
        ServiceCategoryListProPresentationQuery $serviceCategoryListProPresentationQuery,
    ): array {
        // See docs/future-improvements/validate-pro-exists-for-category-presentation-read.md
        return $this->serviceCategoryDao->getListForProPresentation($serviceCategoryListProPresentationQuery->id);
    }
}
