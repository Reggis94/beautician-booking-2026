<?php

namespace App\Services\Application\Service\Command;

final class DeleteServiceCommand
{
    public function __construct(
        public int $id,
        public int $proId
    ) {
    }
}
