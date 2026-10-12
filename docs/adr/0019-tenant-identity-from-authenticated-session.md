# ADR 0019: Tenant Identity From the Authenticated Session

## Context
Beautician (pro) endpoints accepted the professional ID (`proId`, `pro_id`) from query parameters or request
bodies. Any caller could read or change another beautician's data by sending another ID.

Beauticians now authenticate with a passwordless email login link (`ProAuth`). A successful login creates an
opaque session identifier, stored hashed in `pro_session` and sent to the browser in an HTTP-only cookie.

## Decision
- The `pro` firewall authenticates `/api/*` and `/pro/*` requests from the session cookie, validated against the
  database on every request. It is stateless: no PHP session is used.
- Endpoints that read or change a beautician's own data are listed in `security.yaml` `access_control` with
  `ROLE_PRO`, and return HTTP 401 with an empty body without a valid session.
- Their controllers take the beautician with `#[CurrentUser] AuthenticatedPro` and use
  `AuthenticatedPro::getId()` as the tenant pro ID. A pro ID sent by the client is ignored.
- Resources addressed by their own ID (service, service category) are still checked to belong to that pro ID
  in the application layer before any change.
- Public endpoints (pro presentation, public booking, tracking, login) stay out of `access_control`. A stale or
  invalid cookie never blocks them.
- State-changing requests carrying the session cookie, and all state-changing `/api/auth/*` requests, must come
  from the application itself or an origin listed in `AUTH_ALLOWED_ORIGINS` (CSRF protection), on top of the
  `SameSite` cookie.

## Consequences
- New beautician endpoints must be added to `access_control` and take the pro ID from `AuthenticatedPro`.
- Pro-admin endpoints no longer take a `proId` parameter.
- Login tokens and session identifiers are never stored or logged in clear, and never returned in a body.
