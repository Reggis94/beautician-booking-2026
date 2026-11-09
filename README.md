# Beautician Booking 2026

## AI Collaboration Notes

`AGENTS.md` defines how AI assistants should operate in this repo to maximize productivity and quality. It requires consulting this README for architecture context and following ADRs in `docs/adr` as the source of architectural decisions, while updating those records when new decisions are made. In practice: AI work starts here for context, aligns with existing ADRs, create new ADRs and updates README when architecture changes.

## Coding Standard

All PHP code must be PSR-12 compliant.

## Transitioning to DDD

This project is transitioning from a conventional layered structure to Domain-Driven Design (DDD). The goal is to make business rules around availability explicit, resilient, and easier to change as the product evolves. DDD keeps domain logic close to the language of the business and reduces coupling to framework and delivery concerns, which improves testability and maintainability.

## DDD Scope (Full vs Lite)

Some domains have a dedicated `Domain` layer, others do not. We use full DDD only where invariants are heavy (entities + repositories + explicit domain layer). For simpler domains we use DDD-lite: domain logic stays in the `Application` layer and uses DAOs or application-level repository contracts instead of a full domain model. This balance (along with partial hexagonal patterns) is intentional to avoid over-engineering.

Full DDD:
- `Availability`
- `Services`

DDD-lite:
- `ProfilePro`
- `Booking`
- `Tracking`
- `Store`
- `ProAuth`

Service categories are entities of the `Services` domain (`ServiceCategory`). Services and categories are
versioned: content with appointment history is replaced, never overwritten, and catalog writes are serialized
per professional with the booking lock; see ADR 0022. Full-DDD repositories also use explicit DBAL SQL.
DDD-lite persistence prefers narrow application DAO contracts implemented with
explicit Doctrine DBAL SQL; see ADR 0015.

## Partial Hexagonal Architecture (ProfilePro)

ProfilePro applies a partial hexagonal (ports and adapters) approach. For example, the application defines a `TimezoneResolverPortInterface` and provides a `TimezoneDbAdapter` for the TimezoneDb API. This keeps the core logic independent from a specific provider and makes it possible to swap to another timezone API with minimal change.

`Services` also uses a partial hexagonal port for service image files (`ServiceImageStorageInterface`, local
filesystem adapter). Files live outside the project in `SERVICE_IMAGE_STORAGE_DIRECTORY` (default `../pro-banners`),
live under `services/<pro-id>/`, removed ones in the shared `services/deleted/` folder.

## ProAuth (Passwordless Beautician Authentication)

Beauticians (pros) log in with a single-use link emailed synchronously through Symfony Mailer with the
Mailchimp Transactional API transport (`MAILER_DSN=mandrill+api://KEY@default`, needs `symfony/http-client`).
Confirming the link opens a database session (`pro_session`) whose opaque identifier is set in an HTTP-only
cookie. Tokens and session identifiers are stored as SHA-256 hashes only. ProAuth applies partial hexagonal
architecture for email delivery (`LoginLinkMailerInterface` port, Symfony Mailer adapter).

- Every beautician endpoint takes the tenant pro ID from the session, never from the request (see ADR 0019).
- Login responses never reveal whether an email exists. Unknown emails are ignored and not stored.
- Login link requests per beautician have a 60-second cooldown and a limit of 24 per sliding hour, counted from
  `pro_login_token` rows. Both are separate application classes (`LoginLinkCooldown`, `LoginLinkRateLimit`),
  combined by `LoginLinkThrottle::isAllowed()`;
  refused requests store nothing and still answer `202`. When the email fails to send, for any reason, the row is
  kept and counted but marked `delivery_failed_at`, which makes its token unusable.
- The API is same-origin only: no CORS, and state-changing requests are checked against the API's own origin
  (CSRF, see `FrontendOriginSubscriber`).
- Environment: `AUTH_LOGIN_CONFIRM_URL` (frontend confirmation page, required), `AUTH_SESSION_COOKIE_SECURE` (true),
  `AUTH_SESSION_COOKIE_SAMESITE` (lax), `AUTH_SESSION_COOKIE_MAX_AGE_SECONDS`, `MAILER_FROM_EMAIL` (required),
  `MAILER_FROM_NAME`, and in dev `MAILER_DEV_RECIPIENT`, which receives every email.
- ProAuth routes are loaded in every environment; the `Availability`, `ProfilePro`, `Booking` and `Services` API routes are still `dev`-only.
- Pages: `/fr/connexion` (request a link) and `/fr/connexion/confirmation` (the emailed link) are Twig pages in the ProAuth UI layer
  that call the API from the browser. French pages use French URLs under `/fr` (ADR 0021); English templates are
  kept aligned but not served yet.
  Signed-out visits to protected `/pro/` and `/fr/pro/` pages redirect to `/fr/connexion`.
- Frontend integration: `docs/pro-auth-frontend-integration.md`.

## ProAdmin Demo Environment

ProAdmin and public frontend templates that use fake data are an isolated customer demo environment. Demo routes must use a `/demo` path prefix and remain usable without routing users into missing pages or backend errors. Equivalent backend-connected templates should be built for the real app later, and demo templates must stay aligned with those real templates. For real templates, backend API contracts take precedence over frontend assumptions from the demo. See `docs/pro-admin-demo-environment.md`.

## Architecture Decision Records (ADRs)

This project includes Architecture Decision Records under `docs/adr`:
- 0002: Prefer Single-Type Parameters and Properties
- 0003: PSR-12 Coding Standard
- 0004: DDD Scope (Full vs DDD-lite)
- 0005: Trailing Newline in Non-PHP Files
- 0006: Application Interfaces for External Concrete Classes
- 0007: Remove Implemented Future-Improvement Docs
- 0008: Avoid Unsolicited Data in Responses
- 0009: Automatic Request Mapping to Commands and Queries
- 0010: Monitor Comment ID Matches Monitor Doc ID
- 0011: GET Endpoints Read Input From Query Parameters
- 0012: ServiceCategory Owns Service Category Code
- 0013: Datetime Timezone Policy
- 0014: Account for Environment Differences
- 0015: Prefer SQL DAOs for DDD-lite Data Access
- 0016: Avoid Arrow Functions for Readability
- 0019: Tenant Identity From the Authenticated Session
- 0020: ProAuth Tests as Executable Requirements
- 0021: French Pages Use French URLs
- 0022: Versioned Service Catalog in a Full-DDD Services Domain
