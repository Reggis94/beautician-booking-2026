# Audit Datetime Timezone Boundaries

## Context
ADR 0013 defines the timezone policy:
- Appointments are stored as UTC instants.
- Availabilities are professional-local schedules based on `pro.timezone_iana`.
- A professional appointment's local display time is derived by converting the
  UTC appointment datetime to that professional's IANA timezone.

Some current code paths should be double-checked before relying on them for
production timezone correctness.

## Improvement
Audit and adjust appointment and availability datetime handling.

Check at least:
- Appointment upcoming/past SQL comparisons against "now".
- Appointment response formatting for ProAdmin display.
- Appointment overlap checks that cast UTC parameters to SQL timestamps.
- Availability local-window generation through `pro.timezone_iana`.
- Availability filtering for today's local date and local time.
- DST transition behavior for professional timezones.

## Testing
Add automated tests covering:
- A professional in a non-UTC timezone.
- An appointment that is upcoming in UTC but could be misclassified if compared
  against the database session timezone.
- Availability windows crossing daylight-saving boundaries.
- ProAdmin appointment display converted from UTC to the professional's local
  timezone.

## QA Notes
When this improvement is implemented, include GitHub-issue-ready QA with:
- Preconditions.
- Setup SQL that creates or updates a professional with `timezone_iana`.
- Postman requests for appointment creation and appointment listing.
- cURL requests for the same API calls.
- Expected UTC storage values and expected professional-local display values.
- Verification SQL for `appointment.start_dt`, `appointment.end_dt`, and
  `pro.timezone_iana`.
- Cleanup SQL.
