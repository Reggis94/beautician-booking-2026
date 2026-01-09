<?php

namespace App\Services\Application\Service\CommandHandler;

use App\Services\Application\Service\Command\CreateServiceCommand;
use App\Services\Application\Service\Dao\ServiceDaoInterface;

final class CreateServiceCommandHandler
{
    public function __construct(private readonly ServiceDaoInterface $serviceDao)
    {
    }

    public function __invoke(CreateServiceCommand $command): void
    {
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
