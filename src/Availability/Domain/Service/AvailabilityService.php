<?php

namespace App\Availability\Domain\Service;

use App\Availability\Domain\ValueObject\WeekAvailability;

class AvailabilityService
{
    public static function resolveExistingOverlaps(array $newAvailabilities, array $existingAvailabilities): array
    {
        // Future improvement: pass the backend-calculated replacement range explicitly so the resolver can
        // handle a fully closed week where no new open availability rows exist.
        // See docs/future-improvements/availability/explicit-day-by-day-week-upsert.md.
        $newRangeStartDateTime = null;
        $newRangeEndDateTime = null;
        $newStartDateTime = null;
        $newEndDateTime = null;
        foreach ($newAvailabilities as $newAvailability) {
            if (!$newAvailability instanceof WeekAvailability) {
                throw new \InvalidArgumentException('Should be an instance of WeekAvailability');
            }

            $newStartDateTime = $newAvailability->getStartDateTime();
            $newEndDateTime = $newAvailability->getEndDateTime();

            if ($newRangeStartDateTime === null || $newRangeEndDateTime === null) {
                $newRangeStartDateTime = $newStartDateTime;
                $newRangeEndDateTime = $newEndDateTime;
                continue;
            }

            if ($newRangeStartDateTime != $newStartDateTime || $newRangeEndDateTime != $newEndDateTime) {
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

            if (!($existingStartDateTime <= $newRangeEndDateTime && $newRangeStartDateTime <= $existingEndDateTime)) {
                throw new \LogicException(
                    __FUNCTION__ . ' requires that all existing availabilities overlap the new availability week range. This violates the repository query contract.'
                );
            }

            if ($newRangeStartDateTime <= $existingStartDateTime && $existingEndDateTime <= $newRangeEndDateTime) {
                continue;
            }

            if ($existingStartDateTime < $newRangeStartDateTime) {
                $before[] = new WeekAvailability(
                    $existingAvailability->getProId(),
                    $existingStartDateTime,
                    $beforeEndDateTime,
                    $existingAvailability->getDayOfWeek(),
                    $existingAvailability->getStartTime(),
                    $existingAvailability->getEndTime()
                );
            }

            if ($existingEndDateTime > $newRangeEndDateTime) {
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
