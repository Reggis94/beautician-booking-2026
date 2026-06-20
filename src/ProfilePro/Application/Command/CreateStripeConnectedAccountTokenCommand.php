<?php

namespace App\ProfilePro\Application\Command;

use App\ProfilePro\Application\Finance\Dto\CreateStripeConnectedAccountTokenDto;

final readonly class CreateStripeConnectedAccountTokenCommand
{
    public function __construct(private CreateStripeConnectedAccountTokenDto $dto)
    {
    }

    public function getDto(): CreateStripeConnectedAccountTokenDto
    {
        return $this->dto;
    }
}
