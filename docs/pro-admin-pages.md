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
| `demo_pro_admin_dashboard_fr` | `GET` | `/fr/demo/pro/dashboard` | French demo ProAdmin landing page. |
| `demo_pro_admin_calendar_fr` | `GET` | `/fr/demo/pro/dashboard/calendar` | French-only weekly demo calendar. |
| `demo_pro_admin_appointments_upcoming_fr` | `GET` | `/fr/demo/pro/dashboard/upcoming` | French demo upcoming appointments page. |
| `demo_pro_admin_appointments_past_fr` | `GET` | `/fr/demo/pro/dashboard/past` | French demo past appointments page. |
| `demo_pro_admin_services_index_fr` | `GET` | `/fr/demo/pro/dashboard/services` | French demo Prestations page: services and categories, browser-only fake data. |
| `demo_pro_admin_opening_hours_fr` | `GET` | `/fr/demo/pro/dashboard/opening-hours` | French demo opening hours page. |
| `demo_front_pro_home_fr` | `GET` | `/fr/demo/pro` | French demo public frontend page. |
| `g4s_checkout` | `GET` | `/checkout` | Public checkout entry point for professionals creating a G4S account. |
| `pro_admin_appointments_upcoming_fr` | `GET` | `/fr/pro/dashboard/upcoming` | Backend-connected upcoming appointments page (French). Requires the beautician session; signed-out visitors are redirected to `/fr/connexion`. |
| `pro_admin_services_index_fr` | `GET` | `/fr/pro/tableau-de-bord/prestations` | Backend-connected Prestations page (French, `dev` only like the Services API): services and categories of the logged-in beautician. Same template as the demo page (`pro_admin/fr/_prestations.html.twig`). |

## API routes

These API routes are for ProAdmin frontend data calls. They read real database
data for the requested professional through DDD-lite application queries and
infrastructure read methods. They require the beautician session cookie and act
on the logged-in professional only: the professional ID comes from the session,
never from the request (see ADR 0019).

The backend-connected upcoming appointments page calls the upcoming
appointments API from the browser. The remaining API routes are available for
future backend-connected pages. Appointment routes live in the Booking UI HTTP
layer; service routes live in the Services UI HTTP layer.

| Route name | Method | Path | Purpose |
| --- | --- | --- | --- |
| `api_pro_list_upcoming_appointments` | `GET` | `/api/pro/appointment/upcoming` | Returns upcoming appointments for the professional. |
| `api_pro_list_past_appointments` | `GET` | `/api/pro/appointment/past` | Returns past appointments for the professional. |
| `api_pro_get_service_catalog` | `GET` | `/api/pro/service/catalog` | Returns the Prestations catalog: not deleted categories with their services, then uncategorized services, with images and appointment counts. |
| `api_pro_create_service_category` | `POST` | `/api/pro/service/category/new` | Creates a category. JSON `name`. |
| `api_pro_rename_service_category` | `PUT` | `/api/pro/service/category/edit` | Renames a category (replaces it when appointments reference it). JSON `serviceCategoryId`, `name`. |
| `api_pro_delete_service_category` | `DELETE` | `/api/pro/service/category/delete` | Deletes a category. JSON `serviceCategoryId`, `keepServices`. |
| `api_pro_create_service` | `POST` | `/api/pro/service/new` | Creates a service with its images (multipart form). |
| `api_pro_update_service` | `POST` | `/api/pro/service/edit` | Saves a service form: content, publication, kept and new images (multipart form). |
| `api_pro_delete_service` | `DELETE` | `/api/pro/service/delete` | Deletes a service. JSON `serviceId`. |

Each controller documents its fields and statuses. Error responses have an empty body (ADR 0008): 404 for an
unknown, deleted or foreign resource, 409 for a duplicate category name or more than two images, 422 for
invalid input.

Service catalog response:
- `categories`: list of `id`, `name`, `is_active` (has a published service), `appointment_count`, `services`.
- `uncategorized`: services without a category.
- Each service: `id`, `name`, `description`, `duration_min`, `price_cents`, `category_id`, `published`,
  `appointment_count`, `images` (`id`, `url`).

Public presentation routes (no session): `GET /api/pro-presentation/category/list?id={proId}`,
`GET /api/pro-presentation/service/list?id={categoryId}&proId={proId}`,
`GET /api/pro-presentation/service/uncategorized/list?id={proId}` and the image files
`GET /service-images/{proId}/{fileName}`. They only expose published, not deleted services.

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
