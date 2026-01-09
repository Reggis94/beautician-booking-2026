<?php

namespace App\Services\Application\Service\CommandHandler;

use App\Services\Application\Service\Command\CreateServiceCommand;
use App\Services\Application\Service\Dao\ServiceDaoInterface;
use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;

final class CreateServiceCommandHandler
{
    public function __construct(
        private readonly ServiceDaoInterface $serviceDao,
        private readonly ServiceCategoryDaoInterface $serviceCategoryDao
    ) {
    }

    public function __invoke(CreateServiceCommand $command): void
    {
        if ($command->categoryId !== null
            && !$this->serviceCategoryDao->doesBelongToPro($command->categoryId, $command->proId)
        ) {
            throw new \DomainException('The service category does not belong to pro');
        }

        $this->serviceDao->create(
            $command->proId,
            $command->categoryId,
            $command->name,
            $command->description,
            $command->durationMin,
            $command->priceCents
        );
    }
}
