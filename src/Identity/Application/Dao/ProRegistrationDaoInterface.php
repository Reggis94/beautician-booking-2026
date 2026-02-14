<?php

namespace App\Identity\Application\Dao;

interface ProRegistrationDaoInterface
{
    public function create(string $firstName, string $lastName, string $email, string $confirmationToken): void;
}
