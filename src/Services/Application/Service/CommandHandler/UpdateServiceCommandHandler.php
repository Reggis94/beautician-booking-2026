<?php

namespace App\Services\Application\Service\CommandHandler;

use App\Services\Application\Service\Command\UpdateServiceCommand;
use App\Services\Application\Service\Dao\ServiceDaoInterface;
use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;

final class UpdateServiceCommandHandler
{
    public function __construct(
        private readonly ServiceDaoInterface $serviceDao,
        private readonly ServiceCategoryDaoInterface $serviceCategoryDao
    ) {
    }

    public function __invoke(UpdateServiceCommand $command): void
    {
        if (!$this->serviceDao->doesBelongToPro($command->id, $command->proId)) {
            throw new \DomainException('The service does not belong to pro');
        }

        if (
            $command->categoryId !== null
            && !$this->serviceCategoryDao->doesBelongToPro($command->categoryId, $command->proId)
        ) {
            throw new \DomainException('The service category does not belong to pro');
        }

        $this->serviceDao->update(
            $command->id,
            $command->categoryId,
            $command->name,
            $command->description,
            $command->durationMin,
            $command->priceCents
        );
    }
}
