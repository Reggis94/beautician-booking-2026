<?php

namespace App\Availability\Domain\Service;

use App\Availability\Domain\ValueObject\WeekAvailability;

class AvailabilityService
{
    // TODO: Implement DST-safe, multi-timezone, and non-UK logic before 2026-03-29 (current approach becomes obsolete).
    public static function resolveExistingOverlaps(array $newAvailabilities, array $existingAvailabilities): array
    {
        $newRange = null;
        $newStartDateTime = null;
        $newEndDateTime = null;
        foreach ($newAvailabilities as $newAvailability) {
            if (!$newAvailability instanceof WeekAvailability) {
                throw new \InvalidArgumentException('Should be an instance of WeekAvailability');
            }

            $newStartDateTime = $newAvailability->getStartDateTime();
            $newEndDateTime = $newAvailability->getEndDateTime();

            $startTs = $newStartDateTime->getTimestamp();
            $endTs = $newEndDateTime->getTimestamp();

            if ($newRange === null) {
                $newRange = [$startTs, $endTs];
                continue;
            }

            if ($newRange[0] !== $startTs || $newRange[1] !== $endTs) {
                // Duplicated week range consistency check in UpsertWeekAvailabilityCommandHandler and UpsertWeekAvailabilityController.
                throw new \LogicException('All new availabilities must share the same week range.');
            }
        }

        if (empty($existingAvailabilities)) {
            throw new \LogicException(__FUNCTION__ . ' requires at least one overlapping availability.');
        }

        $before = [];
        $after = [];
        $beforeEndDateTime = (clone $newStartDateTime)
            ->modify('-1 day')
            ->setTime(23, 59);
        $afterStartDateTime = (clone $newEndDateTime)
            ->modify('+1 day')
            ->setTime(0, 0);

        foreach ($existingAvailabilities as $existingAvailability) {
            if (!$existingAvailability instanceof WeekAvailability) {
                throw new \InvalidArgumentException('Should be an instance of WeekAvailability');
            }

            $existingStartDateTime = $existingAvailability->getStartDateTime();
            $existingEndDateTime = $existingAvailability->getEndDateTime();

            $existingStartTs = $existingStartDateTime->getTimestamp();
            $existingEndTs = $existingEndDateTime->getTimestamp();

            if (!($existingStartTs <= $newRange[1] && $newRange[0] <= $existingEndTs)) {
                throw new \LogicException(
                    __FUNCTION__ . ' requires that all existing availabilities overlap the new availability week range. This violates the repository query contract.'
                );
            }

            if ($newRange[0] <= $existingStartTs && $existingEndTs <= $newRange[1]) {
                continue;
            }

            if ($existingStartTs < $newRange[0]) {
                $before[] = new WeekAvailability(
                    $existingAvailability->getProId(),
                    $existingStartDateTime,
                    $beforeEndDateTime,
                    $existingAvailability->getDayOfWeek(),
                    $existingAvailability->getStartTime(),
                    $existingAvailability->getEndTime()
                );
            }

            if ($existingEndTs > $newRange[1]) {
                $after[] = new WeekAvailability(
                    $existingAvailability->getProId(),
                    $afterStartDateTime,
                    $existingEndDateTime,
                    $existingAvailability->getDayOfWeek(),
                    $existingAvailability->getStartTime(),
                    $existingAvailability->getEndTime()
                );
            }
        }

        return array_merge($before, $newAvailabilities, $after);
    }
}
