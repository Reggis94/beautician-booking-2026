<?php

namespace App\Services\Application\ServiceCategory\CommandHandler;

use App\Services\Application\ServiceCategory\Command\DeleteServiceCategoryCommand;
use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;

final class DeleteServiceCategoryCommandHandler
{
    public function __construct(private ServiceCategoryDaoInterface $serviceCategoryDao)
    {
    }

    public function __invoke(DeleteServiceCategoryCommand $command): void
    {
        if ($this->serviceCategoryDao->doesBelongToPro((int) $command->id, (int) $command->proId)) {
            $this->serviceCategoryDao->delete($command->id);

            return;
        }

        throw new \DomainException('The service category does not belong to pro');
    }
}
