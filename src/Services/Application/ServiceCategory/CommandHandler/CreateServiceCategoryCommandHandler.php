<?php

namespace App\Services\Application\ServiceCategory\CommandHandler;

use App\Services\Application\ServiceCategory\Command\CreateServiceCategoryCommand;
use App\Services\Domain\Entity\ServiceCategory;
use App\Services\Domain\Exception\ServiceCategoryNameAlreadyUsedException;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\Repository\ServiceRepositoryInterface;

final class CreateServiceCategoryCommandHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly ServiceCategoryRepositoryInterface $categories
    ) {
    }

    /**
     * @throws ServiceCategoryNameAlreadyUsedException
     */
    public function __invoke(CreateServiceCategoryCommand $command, int $proId): void
    {
        $this->services->beginTransaction();

        try {
            $this->services->lockCatalog($proId);

            $category = ServiceCategory::create($proId, $command->name);
            if ($this->categories->isNameUsed($proId, $category->getName(), null)) {
                throw new ServiceCategoryNameAlreadyUsedException();
            }

            $this->categories->save($category);
            $this->services->commit();
        } catch (\Throwable $exception) {
            $this->services->rollBack();

            throw $exception;
        }
    }
}
