<?php

namespace App\Services\Application\ServiceOption\CommandHandler;

use App\Services\Application\Service\Dao\ServiceDaoInterface;
use App\Services\Application\ServiceOption\Command\CreateServiceOptionCommand;
use App\Services\Application\ServiceOption\Dao\ServiceOptionDaoInterface;

final class CreateServiceOptionCommandHandler
{
    public function __construct(
        private readonly ServiceOptionDaoInterface $serviceOptionDao,
        private readonly ServiceDaoInterface $serviceDao
    ) {
    }

    public function __invoke(CreateServiceOptionCommand $command): void
    {
        if (!$this->serviceDao->doesBelongToPro($command->serviceId, $command->proId)) {
            throw new \DomainException('The service does not belong to pro');
        }

        $this->serviceOptionDao->create(
            $command->serviceId,
            $command->name,
            $command->priceExtraCents
        );
    }
}
