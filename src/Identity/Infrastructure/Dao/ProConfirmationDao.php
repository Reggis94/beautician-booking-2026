<?php

namespace App\Identity\Infrastructure\Dao;

use App\Identity\Application\Dao\ProConfirmationDaoInterface;
use App\Identity\Application\Enum\ProConfirmationStatus;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ProConfirmationDao implements ProConfirmationDaoInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getConfirmationStatusByEmail(string $proEmail): ProConfirmationStatus
    {
        $result = $this->connection->fetchOne(
            'SELECT confirmed_at FROM pro WHERE email = :email AND deleted_at IS NULL',
            ['email' => $proEmail],
            ['email' => Types::STRING]
        );

        if ($result === false) {
            return ProConfirmationStatus::PRO_NOT_FOUND;
        }

        if ($result === null) {
            return ProConfirmationStatus::NOT_CONFIRMED;
        }

        return ProConfirmationStatus::CONFIRMED;
    }
}
