<?php

namespace App\Services\Application\ServiceCategory\CommandHandler;

use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\ServiceCategory\Command\RenameServiceCategoryCommand;
use App\Services\Domain\Exception\ServiceCategoryNameAlreadyUsedException;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\Service\ServiceNewVersionDecision;

/**
 * Renames a category in place when no appointment references it.
 *
 * Otherwise the category is replaced by a renamed version: its booked services are replaced too, so their
 * appointments keep the old category name, and its other services are moved directly to the replacement.
 * Everything happens in one transaction.
 */
final class RenameServiceCategoryCommandHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly ServiceCategoryRepositoryInterface $categories
    ) {
    }

    /**
     * @throws ServiceCategoryNotFoundException
     * @throws ServiceCategoryNameAlreadyUsedException
     */
    public function __invoke(RenameServiceCategoryCommand $command, int $proId): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->services->beginTransaction();

        try {
            $this->services->lockCatalog($proId);

            $category = $this->categories->findNotDeleted((int) $command->serviceCategoryId, $proId);
            if ($category === null) {
                throw new ServiceCategoryNotFoundException();
            }

            if ($category->hasName($command->name)) {
                $this->services->commit();

                return;
            }

            if ($this->categories->isNameUsed($proId, trim($command->name), $category->getId())) {
                throw new ServiceCategoryNameAlreadyUsedException();
            }

            if ($this->categories->countAppointments((int) $category->getId()) === 0) {
                $category->rename($command->name);
                $this->categories->save($category);
                $this->services->commit();

                return;
            }

            $replacement = $category->replaceWith($command->name, $now);
            $this->categories->save($category);
            $this->categories->save($replacement);

            $categoryServices = $this->services->findNotDeletedInCategory(
                $proId,
                (int) $category->getId(),
                withAppointmentCount: true
            );

            foreach ($categoryServices as $service) {
                $newContent = $service->getContent()->withCategoryId($replacement->getId());

                if (ServiceNewVersionDecision::isRequired($service, $newContent)) {
                    $newVersion = $service->replaceWith($newContent, $now);
                    $this->services->save($service);
                    $this->services->save($newVersion);
                } else {
                    $service->changeContent($newContent);
                    $this->services->save($service);
                }
            }

            $this->services->commit();
        } catch (\Throwable $exception) {
            $this->services->rollBack();

            throw $exception;
        }
    }
}
