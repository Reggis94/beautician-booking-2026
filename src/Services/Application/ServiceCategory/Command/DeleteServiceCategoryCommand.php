<?php

namespace App\Services\Application\ServiceCategory\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the JSON body of DELETE /api/pro/service/category/delete.
 *
 * keepServices true moves the category's services to "no category"; false deletes them with it.
 */
final class DeleteServiceCategoryCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly ?int $serviceCategoryId = null,
        public readonly bool $keepServices = true
    ) {
    }
}
