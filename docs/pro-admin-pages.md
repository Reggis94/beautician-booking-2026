# ProAdmin Pages

ProAdmin is the protected area where a professional manages appointments,
services, and future account settings. It is separate from Presentation pages,
which are public customer-facing pages used to show services and book
appointments.

The demo page routes render fake data while the ProAdmin frontend is still being
shaped. Fake-data ProAdmin screens belong to the isolated customer demo
environment described in `docs/pro-admin-demo-environment.md`, and their paths
must use the `/demo` prefix. Real ProAdmin page routes use the same layout where
possible and load backend data through the ProAdmin API routes.

## Page routes

| Route name | Method | Path | Purpose |
| --- | --- | --- | --- |
| `demo_pro_admin_dashboard` | `GET` | `/demo/pro/dashboard` | Demo ProAdmin landing page, currently showing upcoming appointments. |
| `demo_pro_admin_appointments_upcoming` | `GET` | `/demo/pro/dashboard/upcoming` | Demo upcoming appointments managed by the professional. |
| `demo_pro_admin_appointments_past` | `GET` | `/demo/pro/dashboard/past` | Demo past appointment history for the professional. |
| `demo_pro_admin_services_index` | `GET` | `/demo/pro/dashboard/services` | Demo services managed by the professional. |
| `demo_pro_admin_opening_hours` | `GET` | `/demo/pro/dashboard/opening-hours` | Demo opening hours managed by the professional. |
| `demo_front_pro_home` | `GET` | `/demo/pro` | Demo public frontend page with fake presentation data and a return link to ProAdmin. |
| `g4s_checkout` | `GET` | `/checkout` | Public checkout entry point for professionals creating a G4S account. |
| `pro_admin_appointments_upcoming` | `GET` | `/pro/dashboard/upcoming?proId={proId}` | Backend-connected upcoming appointments page. |

## API routes

These API routes are for ProAdmin frontend data calls. They read real database
data for the requested professional through DDD-lite application queries and
infrastructure read methods. Authentication and deriving the professional ID
from the connected user are intentionally deferred to a later ticket. For now,
the professional ID is received as `proId` in the query string because these
are `GET` endpoints.

The backend-connected upcoming appointments page calls the upcoming
appointments API from the browser. The remaining API routes are available for
future backend-connected pages. Appointment routes live in the Booking UI HTTP
layer; service routes live in the Services UI HTTP layer.

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

The customer demo routes should remain under `/demo` and stay aligned with the
real templates.
