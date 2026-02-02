<?php

namespace App\Services\Application\ServiceCategory\Command;

final class CreateServiceCategoryCommand
{
    public readonly string $name;
    public readonly int $proId;

    public function __construct(string $name, int $proId)
    {
        $this->name = trim($name);
        $this->proId = $proId;
    }
}
