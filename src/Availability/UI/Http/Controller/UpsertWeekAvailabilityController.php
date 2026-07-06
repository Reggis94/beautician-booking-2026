<?php

namespace App\Availability\UI\Http\Controller;

use App\Availability\Application\CommandHandler\UpsertWeekAvailabilityCommandHandler;
use App\Availability\Application\Command\UpsertWeekAvailabilityCommand;
use App\Availability\Domain\ValueObject\WeekAvailability;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/upsert-week-availability', name: 'upsertWeekAvailability', methods: ['POST'])]
class UpsertWeekAvailabilityController
{
    public function __invoke(Request $request, UpsertWeekAvailabilityCommandHandler $handler): JsonResponse
    {
        // TODO: Authentication/authorization for this endpoint will be handled in a follow-up ticket.
        $data = json_decode($request->getContent(), true) ?? [];
        try {
            // Future improvement: add a one-day upsert that rebuilds the target week from existing storage
            // plus the submitted day, then stores only open days for the resolved week range.
            // See docs/future-improvements/availability/explicit-day-by-day-week-upsert.md.
            if (!is_array($data) || $data === []) {
                throw new \InvalidArgumentException('Payload must be a non-empty array of availabilities.');
            }

            $newAvailabilities = [];
            $proId = null;
            $weekRange = null;

            foreach ($data as $item) {
                if (!is_array($item)) {
                    throw new \InvalidArgumentException('Each availability payload item must be an object.');
                }

                $weekAvailability = new WeekAvailability(
                    isset($item['proId']) ? (int) $item['proId'] : 0,
                    new \DateTimeImmutable($item['startDateTime'] ?? ''),
                    new \DateTimeImmutable($item['endDateTime'] ?? ''),
                    isset($item['dayOfWeek']) ? (int) $item['dayOfWeek'] : 0,
                    (string) ($item['startTime'] ?? ''),
                    (string) ($item['endTime'] ?? '')
                );

                if ($proId === null) {
                    $proId = $weekAvailability->getProId();
                } elseif ($proId !== $weekAvailability->getProId()) {
                    // Duplicated proId consistency check in UpsertWeekAvailabilityCommandHandler.
                    throw new \LogicException('All availabilities must share the same proId.');
                }

                $startTs = $weekAvailability->getStartDateTime()->getTimestamp();
                $endTs = $weekAvailability->getEndDateTime()->getTimestamp();
                if ($weekRange === null) {
                    $weekRange = [$startTs, $endTs];
                } elseif ($weekRange[0] !== $startTs || $weekRange[1] !== $endTs) {
                    // Duplicated week range consistency check in UpsertWeekAvailabilityCommandHandler and AvailabilityService::resolveExistingOverlaps.
                    throw new \LogicException('All availabilities must share the same week range.');
                }

                // Future improvement: reject duplicate dayOfWeek values for the same week range.
                // See docs/future-improvements/availability/explicit-day-by-day-week-upsert.md.
                $newAvailabilities[] = $weekAvailability;
            }
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new UpsertWeekAvailabilityCommand($newAvailabilities);
        $handler($command);
        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }
}
