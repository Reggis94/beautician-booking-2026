<?php

namespace App\Availability\Application\CommandHandler;

use App\Availability\Domain\Application\Command\UpsertWeekAvailabilityCommand;
use App\Availability\Domain\Entity\AvailabilityEntity;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;
use App\Availability\Domain\Service\AvailabilityService;
use App\Availability\Domain\ValueObject\WeekAvailability;

final class UpsertWeekAvailabilityCommandHandler
{
    public function __construct(private readonly AvailabilityRepositoryInterface $repository)
    {
    }

    public function __invoke(UpsertWeekAvailabilityCommand $command): int
    {
        $newAvailabilities = $command->getNewAvailabilities();
        if ($newAvailabilities === []) {
            throw new \InvalidArgumentException('newAvailabilities must not be empty.');
        }

        $firstAvailability = $newAvailabilities[0];
        $proId = $firstAvailability->getProId();
        $weekStartDate = $firstAvailability->getStartDateTime();
        $weekEndDate = $firstAvailability->getEndDateTime();
        $weekRange = [$weekStartDate->getTimestamp(), $weekEndDate->getTimestamp()];

        foreach ($newAvailabilities as $newAvailability) {
            if ($newAvailability->getProId() !== $proId) {
                // Duplicated proId consistency check in UpsertWeekAvailabilityController.
                throw new \LogicException('All availabilities must share the same proId.');
            }

            $startTs = $newAvailability->getStartDateTime()->getTimestamp();
            $endTs = $newAvailability->getEndDateTime()->getTimestamp();
            if ($weekRange[0] !== $startTs || $weekRange[1] !== $endTs) {
                // Duplicated week range consistency check in UpsertWeekAvailabilityController and AvailabilityService::resolveExistingOverlaps.
                throw new \LogicException('All availabilities must share the same week range.');
            }
        }

        $overlaps = $this->repository->findOverlaps(
            $proId,
            $weekStartDate,
            $weekEndDate
        );

        if (!empty($overlaps)) {
            $existingAvailabilities = [];
            foreach ($overlaps as $overlap) {
                $existingAvailabilities[] = WeekAvailability::fromAvailabilityEntity(
                    $overlap,
                    $overlap->getProId()
                );
            }

            $resolvedAvailabilities = AvailabilityService::resolveExistingOverlaps(
                $newAvailabilities,
                $existingAvailabilities
            );

            $this->repository->beginTransaction();
            try {
                $this->repository->deleteOverlaps(
                    $proId,
                    $weekStartDate,
                    $weekEndDate
                );

                $lastId = 0;
                foreach ($resolvedAvailabilities as $resolvedAvailability) {
                    $lastId = $this->repository->create(
                        new AvailabilityEntity(
                            $proId,
                            $resolvedAvailability->getStartDateTime(),
                            $resolvedAvailability->getEndDateTime(),
                            $resolvedAvailability->getDayOfWeek(),
                            new \DateTimeImmutable($resolvedAvailability->getStartTime()),
                            new \DateTimeImmutable($resolvedAvailability->getEndTime())
                        )
                    );
                }

                $this->repository->commit();

                return $lastId;
            } catch (\Throwable $exception) {
                $this->repository->rollBack();
                throw $exception;
            }
        }

        $lastId = 0;
        $this->repository->beginTransaction();
        try {
            foreach ($newAvailabilities as $newAvailability) {
                $lastId = $this->repository->create(
                    new AvailabilityEntity(
                        $proId,
                        $newAvailability->getStartDateTime(),
                        $newAvailability->getEndDateTime(),
                        $newAvailability->getDayOfWeek(),
                        new \DateTimeImmutable($newAvailability->getStartTime()),
                        new \DateTimeImmutable($newAvailability->getEndTime())
                    )
                );
            }

            $this->repository->commit();
        } catch (\Throwable $exception) {
            $this->repository->rollBack();
            throw $exception;
        }

        return $lastId;
    }
}
