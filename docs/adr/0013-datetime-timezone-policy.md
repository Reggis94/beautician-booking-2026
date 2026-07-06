# ADR 0013: Datetime Timezone Policy

## Context
Booking and availability flows use datetimes with different meanings.

Appointments represent exact instants. A client chooses an appointment in the
professional's local timezone, but the system must store and compare the
appointment as a UTC instant.

Availabilities represent the professional's local business schedule. A
professional's opening hours are based on local dates and local times, using the
professional timezone stored in `pro.timezone_iana`.

## Decision
Appointment datetimes are stored in UTC. Code that persists or compares
`appointment.start_dt` or `appointment.end_dt` must treat those values as UTC
instants.

Availability dates and times are professional-local business rules. Code that
builds bookable windows from `availability` rows must use the professional's
IANA timezone from `pro.timezone_iana`, defaulting to `UTC` only when existing
code explicitly allows that fallback.

The exact local time of a professional's appointment is not stored separately.
It is derived by converting the UTC appointment datetime to that professional's
IANA timezone from the `pro` table.

## Consequences
- Request boundaries must be explicit about whether incoming datetime values are
  pro-local datetimes or UTC datetimes.
- Application code may convert pro-local appointment requests to UTC before
  persistence.
- SQL that compares appointment datetimes to "now" must avoid accidental use of
  the database session timezone.
- SQL that derives availability windows must convert pro-local dates and times
  through `pro.timezone_iana` before comparing them with UTC appointments.
- ProAdmin appointment responses may need to convert UTC appointment datetimes
  back to the professional's timezone for display.
- Future timezone changes must include tests around UTC storage, pro-local
  availability boundaries, daylight-saving transitions, and API QA examples.
