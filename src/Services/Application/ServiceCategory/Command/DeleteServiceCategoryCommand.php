<?php

namespace App\Services\Application\ServiceCategory\Command;

final class DeleteServiceCategoryCommand
{
    public function __construct(public int $id, public int $proId)
    {
    }
}
