<?php

namespace App\Identity\Application\Query;

final class CheckProConfirmedByEmailQuery
{
    public function __construct(private string $proEmail)
    {
    }

    public function getProEmail(): string
    {
        return $this->proEmail;
    }
}
