<?php

namespace App\Identity\Application\Dao;

use App\Identity\Application\Enum\ProConfirmationStatus;

interface ProConfirmationDaoInterface
{
    public function getConfirmationStatusByEmail(string $proEmail): ProConfirmationStatus;
}
