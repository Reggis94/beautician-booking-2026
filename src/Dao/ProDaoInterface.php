<?php

namespace App\Dao;

interface ProDaoInterface
{
    public function existsById(int $id): bool;
}
