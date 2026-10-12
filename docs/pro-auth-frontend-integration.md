# Beautician Authentication: Frontend Integration

Passwordless login for beauticians (pros). The backend owns the whole session: the frontend never reads, stores
or sends a token or session identifier itself. Only beauticians log in; clients book without an account.

## Endpoints

All request and response bodies are JSON. Error responses have an empty body unless stated otherwise. A `422`
body uses Symfony's validation format (`violations[].propertyPath`, `violations[].title`).

| Purpose | Request | Success | Errors |
| --- | --- | --- | --- |
| Request a login link | `POST /api/auth/login-link` `{"email": "..."}` | `202`, empty body | `422` malformed email |
| Confirm the login link | `POST /api/auth/login` `{"token": "..."}` | `204`, sets the session cookie | `401` invalid or used link, `422` malformed token |
| Authentication status | `GET /api/auth/me` | `200` profile (below) | `401` not authenticated |
| Log out | `POST /api/auth/logout` | `204`, clears the cookie | none (`204` even without a session) |

Any state-changing request from an origin that is not allowed gets `403` (see Configuration).

`GET /api/auth/me` response:

```json
{
    "email": "salon@example.com",
    "link_slug": "salon-a",
    "display_name": "Salon A",
    "firstname": "Alice",
    "lastname": null
}
```

`link_slug` is the beautician's public page slug. No database ID is returned.

## Login Flow

1. **Login page.** Send `POST /api/auth/login-link` with the email. On `202`, always show the same message,
   for example "If this email belongs to an account, you will receive a login link." The response is identical
   whether the email exists or the email failed to send. Do not try to tell these apart.
2. **Email link.** The email links to `AUTH_LOGIN_CONFIRM_URL#token=<token>`. The token is in the URL
   **fragment**, so it never reaches the frontend server or its logs.
3. **Confirmation page.** Read the token from `window.location.hash` (`token` parameter). Opening the page must
   not log in. Show a "Continue to login" button. Remove the fragment from the address bar
   (`history.replaceState`) once read.
4. **Continue to login.** Send `POST /api/auth/login` with `{"token": "<token>"}`.
   - `204`: logged in. Redirect to the dashboard.
   - `401`: the link is invalid or was already used (each link works once). Show a message and a link back to
     the login page to request a new link.
   - `422`: the URL was truncated or altered; handle like `401`.

Links do not expire, but each works only once. Login link requests are limited per beautician, silently:

- **Cooldown:** a request less than 60 seconds after the previous link is ignored.
- **Rate limit:** at most 24 links over the last 60 minutes; further requests are ignored.

Both still answer `202`, so the frontend cannot tell. After a `202`, disable the "Send login link" button for
60 seconds (for example with a visible countdown), then allow a new request. Each accepted request creates a new
link; earlier links stay valid until used.

## Session Handling

- The session lives in the `pro_session` cookie: HTTP-only, `SameSite=Lax`, `Secure` in production, path `/`.
  JavaScript cannot read it and must not try to.
- Send every API request with credentials so the browser sends the cookie:
  `fetch(url, { credentials: 'include' })` (or `withCredentials: true` with axios).
- Sessions do not expire automatically. They end on logout, or if the server revokes them.
- On app load (and route guards), call `GET /api/auth/me`:
  - `200`: authenticated, show the dashboard (the body gives the profile to display).
  - `401`: not authenticated, redirect to the login page.
- Any protected endpoint can return `401` (for example after logout in another tab): redirect to the login page.

## Logout

Send `POST /api/auth/logout` with credentials, then redirect to the login page. It always returns `204` and
the session can never be used again.

## Protected Endpoints

These require the session cookie and return `401` without it. They act on the logged-in beautician only: do
not send `proId` / `pro_id`, which is ignored if present.

- `GET /api/auth/me`
- `GET /api/pro-admin/services`
- `GET /api/pro-admin/appointments/upcoming`, `GET /api/pro-admin/appointments/past`
- `GET /api/availabilities?month=YYYY-MM`
- `POST /api/upsert-week-availability`
- `GET /api/pro/timezone-location/geocode-address`, `PUT /api/pro/timezone-location/update`
- `POST /api/pro/service/new`, `PUT /api/pro/service/edit`, `DELETE /api/pro/service/delete`
- `POST /api/pro/service/category/new`, `DELETE /api/pro/service/category/delete`

Changing or deleting a service or category that belongs to another beautician is rejected (`400`/`403`).

Public endpoints need no session: `/api/pro-presentation/*` (presentation and public booking) and
`/api/tracking/*`.

## Configuration

Backend environment values the frontend depends on:

| Variable | Purpose |
| --- | --- |
| `AUTH_LOGIN_CONFIRM_URL` | Full URL of the frontend confirmation page, for example `https://app.example.com/login/confirm`. The backend appends `#token=...`. |
| `AUTH_ALLOWED_ORIGINS` | Comma-separated frontend origins allowed to call the API from the browser, for example `https://app.example.com`. No trailing slash. |
| `AUTH_SESSION_COOKIE_SECURE` | `true` in production (HTTPS). Set `false` only for local HTTP development. |
| `AUTH_SESSION_COOKIE_SAMESITE` | `lax` by default. |

Requirements:

- **Same site.** The frontend and the API must share the same registrable domain (for example
  `app.example.com` and `api.example.com`), or be served from the same origin. With `SameSite=Lax`, browsers
  do not send the cookie from a different site.
- **CORS.** Requests from an origin in `AUTH_ALLOWED_ORIGINS` receive `Access-Control-Allow-Origin` (that
  origin) and `Access-Control-Allow-Credentials: true`. Preflight requests are answered for `GET`, `POST`,
  `PUT`, `DELETE` with the `Content-Type` header. Other origins are refused.
- **CSRF.** State-changing requests that carry the session cookie, and every `POST /api/auth/*`, must come from
  the API's own origin or an allowed origin (browsers send the `Origin` header automatically). Nothing extra
  is needed in the frontend: no CSRF token.
- In local development, all emails go to `MAILER_DEV_RECIPIENT`.
