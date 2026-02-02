<?php

namespace App\Services\Application\ServiceCategory\CommandHandler;

use App\Dao\ProDaoInterface;
use App\Services\Application\ServiceCategory\Command\CreateServiceCategoryCommand;
use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;

final class CreateServiceCategoryCommandHandler
{
    public function __construct(
        private readonly ServiceCategoryDaoInterface $dao,
        private readonly ProDaoInterface $proDao
    ) {
    }

    public function __invoke(CreateServiceCategoryCommand $command): void
    {
        $name = trim($command->name);
        if ($name === '') {
            throw new \InvalidArgumentException('Category name cannot be empty.');
        }
        $length = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        if ($length > 100) {
            throw new \InvalidArgumentException('Category name must be at most 100 characters.');
        }
        if (!$this->proDao->existsById($command->proId)) {
            throw new \DomainException('Unknown professional.');
        }

        $this->dao->create($command->proId, $name);
    }
}
