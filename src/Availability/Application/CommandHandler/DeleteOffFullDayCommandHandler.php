<?php

namespace App\Availability\Application\CommandHandler;

use App\Availability\Domain\Application\Command\DeleteOffFullDayCommand;
use App\Availability\Domain\Service\OffFullDayService;

final class DeleteOffFullDayCommandHandler
{
    public function __construct(private readonly OffFullDayService $service)
    {
    }

    public function __invoke(DeleteOffFullDayCommand $command): void
    {
        $this->service->delete(
            $command->getProId(),
            $command->getDate()
        );
    }
}
