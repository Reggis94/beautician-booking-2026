# Map Week Availability Upsert Payload

## Context
`UpsertWeekAvailabilityController` currently reads JSON manually with `json_decode()` and maps each array item into a `WeekAvailability` value object.

ADR 0009 prefers Symfony request mapping attributes when they fit an endpoint. This endpoint can use `MapRequestPayload`, but the current top-level JSON array shape makes the command less explicit and leaves no room for future request-level fields.

## Improvement
Replace the top-level array request body with a wrapped object:

```json
{
  "availabilities": [
    {
      "proId": 1,
      "startDateTime": "2026-06-29 00:00",
      "endDateTime": "2026-07-05 23:59",
      "dayOfWeek": 1,
      "startTime": "09:00",
      "endTime": "18:00"
    }
  ]
}
```

Map that object directly into `UpsertWeekAvailabilityCommand`:

```php
public function __invoke(
    #[MapRequestPayload(validationFailedStatusCode: JsonResponse::HTTP_BAD_REQUEST)]
    UpsertWeekAvailabilityCommand $command,
    UpsertWeekAvailabilityCommandHandler $handler
): JsonResponse {
    $handler($command);

    return new JsonResponse(null, JsonResponse::HTTP_CREATED);
}
```

The command should expose an `availabilities` constructor argument matching the JSON key:

```php
final readonly class UpsertWeekAvailabilityCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        /** @var array<int, UpsertWeekAvailabilityItem> */
        public array $availabilities
    ) {
    }
}
```

Each item should be represented by a scalar input object, for example `UpsertWeekAvailabilityItem`, and the handler should convert those validated input values into `WeekAvailability` domain value objects.

## Reason
The wrapper level is recognized by matching the JSON key `availabilities` to the command constructor argument named `$availabilities`. This keeps the controller thin, moves request validation onto the command, and keeps domain value-object construction inside the application flow instead of the HTTP layer.

The wrapped request body also leaves room for future request-level fields such as replacement mode, timezone context, or metadata without another breaking payload-shape change.
