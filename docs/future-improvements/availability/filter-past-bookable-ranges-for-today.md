# Filter Past Bookable Ranges For Today

## Context
`GetBookableDaysForService` currently returns bookable local dates for a service and month.

The SQL excludes dates before the professional's current local date with:

```sql
local_date >= (NOW() AT TIME ZONE timezone_iana)::date
```

This keeps past dates out of the response, but it does not exclude today's date when every free range for today is already in the past.

## Improvement
When evaluating free ranges for today's local date, compare each range against the professional's current local timestamp, derived from `NOW()` in the professional's timezone.

Possible implementation:
- Compute the professional's current local timestamp from `NOW() AT TIME ZONE timezone_iana`.
- For today's `local_date`, clip `free_start` to that current local timestamp.
- Keep the day only when the remaining free range can still fit the requested service duration.
- Preserve the current date-level filtering for future dates.

## Reason
The public booking calendar should not show today's date as bookable when all bookable ranges for that professional are already in the past.
