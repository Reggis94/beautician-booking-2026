<?php

namespace App\Services\Application\ServiceCategory\CommandHandler;

use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\ServiceCategory\Command\DeleteServiceCategoryCommand;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\Service\ServiceNewVersionDecision;

/**
 * Explicitly deletes a category, in one transaction with its services.
 *
 * Keeping the services: booked services are replaced by an uncategorized version (their appointments keep
 * the deleted category); the others are uncategorized in place. Deleting them: every not deleted service of the
 * category is explicitly deleted. Appointments are never cancelled, moved or repriced.
 */
final class DeleteServiceCategoryCommandHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly ServiceCategoryRepositoryInterface $categories
    ) {
    }

    /**
     * @throws ServiceCategoryNotFoundException
     */
    public function __invoke(DeleteServiceCategoryCommand $command, int $proId): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->services->beginTransaction();

        try {
            $this->services->lockCatalog($proId);

            $category = $this->categories->findNotDeleted((int) $command->serviceCategoryId, $proId);
            if ($category === null) {
                throw new ServiceCategoryNotFoundException();
            }

            $category->delete($now);
            $this->categories->save($category);

            $categoryServices = $this->services->findNotDeletedInCategory(
                $proId,
                (int) $category->getId(),
                withAppointmentCount: $command->keepServices
            );

            foreach ($categoryServices as $service) {
                if (!$command->keepServices) {
                    $service->delete($now);
                    $this->services->save($service);

                    continue;
                }

                $newContent = $service->getContent()->withCategoryId(null);

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
