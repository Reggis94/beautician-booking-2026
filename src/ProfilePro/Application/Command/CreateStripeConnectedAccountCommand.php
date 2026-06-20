<?php

namespace App\ProfilePro\Application\Command;

use App\ProfilePro\Application\Finance\Dto\CreateStripeConnectedAccountDto;

final readonly class CreateStripeConnectedAccountCommand
{
    public function __construct(private CreateStripeConnectedAccountDto $dto)
    {
    }

    public function getDto(): CreateStripeConnectedAccountDto
    {
        return $this->dto;
    }
}
