# Reject Duplicate Days In Week Availability Upsert

## Context
`UpsertWeekAvailabilityController` accepts multiple availability rows for one professional and one shared week range.

Today, the request can include the same `dayOfWeek` more than once. The write path stores each row, but `AvailabilityRepository::getAvailabilitiesForMonth()` flattens rows for the same concrete date by returning `MIN(start_time)` and `MAX(end_time)`.

This means duplicate day rows such as `09:00-12:00` and `14:00-18:00` can be exposed as one continuous `09:00-18:00` availability.

## Improvement
Reject duplicate `dayOfWeek` values in the week availability upsert payload.

Possible implementation:
- Track seen `dayOfWeek` values while mapping payload items.
- Return `400 Bad Request` when the same day appears more than once in the same request.
- Add controller or command-handler coverage for duplicate-day rejection.
- Consider adding a database uniqueness constraint for `pro_id`, `week_start_date`, `week_end_date`, and `day_of_week` after existing data has been cleaned.

## Reason
The current API and read model represent one opening interval per day. Rejecting duplicate days prevents silent data shapes that the read side cannot represent correctly.
