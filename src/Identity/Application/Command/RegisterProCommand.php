<?php

namespace App\Identity\Application\Command;

final class RegisterProCommand
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email
    ) {
    }
}
