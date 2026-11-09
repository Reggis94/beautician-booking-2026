<?php

namespace App\Services\Application\Service\CommandHandler;

use App\Services\Application\Exception\ServiceNotFoundException;
use App\Services\Application\Service\Command\DeleteServiceCommand;
use App\Services\Domain\Repository\ServiceRepositoryInterface;

/**
 * Explicit deletion (soft delete). Appointments keep the deleted version unchanged; its images stay
 * attached to it and its files stay in the professional's folder.
 */
final class DeleteServiceCommandHandler
{
    public function __construct(private readonly ServiceRepositoryInterface $services)
    {
    }

    /**
     * @throws ServiceNotFoundException
     */
    public function __invoke(DeleteServiceCommand $command, int $proId): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->services->beginTransaction();

        try {
            $this->services->lockCatalog($proId);

            $service = $this->services->findNotDeleted((int) $command->serviceId, $proId);
            if ($service === null) {
                throw new ServiceNotFoundException();
            }

            $service->delete($now);
            $this->services->save($service);
            $this->services->commit();
        } catch (\Throwable $exception) {
            $this->services->rollBack();

            throw $exception;
        }
    }
}
