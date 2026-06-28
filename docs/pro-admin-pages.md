# ProAdmin Pages

ProAdmin is the protected area where a professional manages appointments,
services, and future account settings. It is separate from Presentation pages,
which are public customer-facing pages used to show services and book
appointments.

These page routes currently render fake data only while the ProAdmin frontend is
still being shaped.

## Page routes

| Route name | Method | Path | Purpose |
| --- | --- | --- | --- |
| `pro_admin_dashboard` | `GET` | `/pro/dashboard` | ProAdmin landing page, currently showing upcoming appointments. |
| `pro_admin_appointments_upcoming` | `GET` | `/pro/dashboard/upcoming` | Upcoming appointments managed by the professional. |
| `pro_admin_appointments_past` | `GET` | `/pro/dashboard/past` | Past appointment history for the professional. |
| `pro_admin_services_index` | `GET` | `/pro/dashboard/services` | Services managed by the professional. |

## API routes

These API routes are for ProAdmin frontend data calls. They read real database
data for the requested professional through DDD-lite application queries and
infrastructure read methods. Authentication and deriving the professional ID
from the connected user are intentionally deferred to a later ticket. For now,
the professional ID is received as `proId` in the query string because these
are `GET` endpoints.

They are intentionally not called by the dashboard template yet. Appointment
routes live in the Booking UI HTTP layer; service routes live in the Services UI
HTTP layer.

| Route name | Method | Path | Purpose |
| --- | --- | --- | --- |
| `api_pro_admin_appointments_upcoming` | `GET` | `/api/pro-admin/appointments/upcoming?proId={proId}` | Returns upcoming appointments for the professional. |
| `api_pro_admin_appointments_past` | `GET` | `/api/pro-admin/appointments/past?proId={proId}` | Returns past appointments for the professional. |
| `api_pro_admin_services_index` | `GET` | `/api/pro-admin/services?proId={proId}` | Returns services managed by the professional. |

Appointment API response fields:
- `id`: appointment identifier.
- `client_name`: client display name.
- `service_name`: booked service display name.
- `start_at`: ISO 8601 datetime.
- `duration_minutes`: appointment duration.

Service API response fields:
- `id`: service identifier.
- `name`: service display name.
- `duration_minutes`: service duration.
- `price_cents`: price in cents.
- `currency`: ISO currency code, currently `USD`.

The page routes can move from fake controller data to these API routes when the
dashboard JavaScript is ready.
