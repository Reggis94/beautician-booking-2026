<?php

namespace App\ProfilePro\Application\Command;

use App\ProfilePro\Application\Finance\Dto\CreateStripeConnectedAccountLinkDto;

final readonly class CreateStripeConnectedAccountLinkCommand
{
    public function __construct(private CreateStripeConnectedAccountLinkDto $dto)
    {
    }

    public function getDto(): CreateStripeConnectedAccountLinkDto
    {
        return $this->dto;
    }
}
