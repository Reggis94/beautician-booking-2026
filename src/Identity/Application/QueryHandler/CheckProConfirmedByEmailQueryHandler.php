<?php

namespace App\Identity\Application\QueryHandler;

use App\Identity\Application\Dao\ProConfirmationDaoInterface;
use App\Identity\Application\Enum\ProConfirmationStatus;
use App\Identity\Application\Query\CheckProConfirmedByEmailQuery;

final class CheckProConfirmedByEmailQueryHandler
{
    public function __construct(private ProConfirmationDaoInterface $proConfirmationDao)
    {
    }

    public function __invoke(CheckProConfirmedByEmailQuery $query): ProConfirmationStatus
    {
        return $this->proConfirmationDao->getConfirmationStatusByEmail($query->getProEmail());
    }
}
