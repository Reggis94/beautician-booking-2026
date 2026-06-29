# Explicit Day Upsert Rebuilding Week Availability

## Context
`UpsertWeekAvailabilityController` currently accepts only open availability rows. The week replacement range is inferred from the first submitted row, and every row must share that same range.

This makes closed days implicit: if a day is absent from the payload, it is treated as closed after the overlapping rows are deleted. That works when at least one day is open, but it cannot represent a fully closed week because the current controller and handler reject an empty availability list.

The pro-admin opening-hours UI edits one day at a time, while the persistence model stores availability as date ranges with one row per open day of week.

## Improvement
Introduce a one-day upsert route for the opening-hours UI. The request should contain only the edited day state. The backend should calculate the target week, load the existing availability for that week, replace only the edited day in memory, and then persist the rebuilt week availability.

Example open-day request:

```json
{
  "proId": 1,
  "date": "2026-07-01",
  "isOpen": true,
  "startTime": "09:00",
  "endTime": "18:00"
}
```

Example closed-day request:

```json
{
  "proId": 1,
  "date": "2026-07-01",
  "isOpen": false,
  "startTime": null,
  "endTime": null
}
```

The backend should derive:
- The replacement week start from the submitted `date`: the Monday of that week at `00:00`.
- The replacement week end from the submitted `date`: the Sunday of that week at `23:59`.
- The edited `dayOfWeek` from `date`.
- The current open days for that week from the database.

Validation rules:
- The submitted `date` must be at least today in the professional/user timezone used for availability management.
- Past dates in that timezone must be rejected before rebuilding the week availability.

Then it should rebuild the new week availability:
- Keep existing open days from the database, except the edited `dayOfWeek`.
- Add the edited day when `isOpen` is true.
- Add nothing for the edited day when `isOpen` is false.
- Reject the rebuilt week if two rows have the same `dayOfWeek`.

## Persistence Rules
- Store only open days in the `availability` table.
- Do not insert rows for closed days.
- If a day was previously open and is now closed, delete the existing open row for that day in the replacement range.
- If all seven days are closed after the rebuild, delete the overlapping open rows and insert nothing.

## Resolver Shape
Keep the resolver just explicit enough for this behavior. Instead of deriving the replacement range from the first new open availability, pass the backend-calculated range directly:

```php
resolveExistingOverlapsForRange(
    int $proId,
    \DateTimeImmutable $rangeStart,
    \DateTimeImmutable $rangeEnd,
    array $newOpenAvailabilities,
    array $existingAvailabilities
)
```

`$newOpenAvailabilities` is the rebuilt week state and may contain zero to seven open rows. It may be empty when the whole week is closed. The resolver should still preserve existing before and after slices outside the replacement range, delete rows covered by the replacement range, and return only rows that should remain stored.

## Reason
This keeps the frontend contract aligned with the one-day editing UI while preserving the existing range-based database model. The backend remains responsible for reconstructing the week safely from persisted state, and closed days stay absent from storage instead of being represented by fake closed rows.
