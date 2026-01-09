<?php

namespace App\Services\Application\Service\CommandHandler;

use App\Services\Application\Service\Command\DeleteServiceCommand;
use App\Services\Application\Service\Dao\ServiceDaoInterface;

final class DeleteServiceCommandHandler
{
    public function __construct(private ServiceDaoInterface $serviceDao)
    {
    }

    public function __invoke(DeleteServiceCommand $command): void
    {
        if ($this->serviceDao->doesBelongToPro((int) $command->id, (int) $command->proId)) {
            $this->serviceDao->delete($command->id);

            return;
        }

        throw new \DomainException('The service does not belong to pro');
    }
}
