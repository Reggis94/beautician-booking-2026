<?php

namespace App\Availability\Application\CommandHandler;

use App\Availability\Domain\Application\Command\CreateOffFullDayCommand;
use App\Availability\Domain\Entity\OffFullDayEntity;
use App\Availability\Domain\Service\OffFullDayService;

final class CreateOffFullDayCommandHandler
{
    public function __construct(private readonly OffFullDayService $service)
    {
    }

    public function __invoke(CreateOffFullDayCommand $command): OffFullDayEntity
    {
        return $this->service->create(
            $command->getProId(),
            $command->getDate()
        );
    }
}
