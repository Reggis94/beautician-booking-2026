# Agent Instructions

## Documentation Use

Always consult `README.md` when making or reviewing changes, to ensure alignment with current architecture guidance and conventions.
When a future improvement in `docs/future-improvements` is implemented, delete its file to keep the backlog accurate.

## Coding Standard

All PHP code must be PSR-12 compliant.

## ADR Usage

Follow the ADRs in `docs/adr` as much as possible.
When a new ADR is created, update the README ADR list to keep the index in sync.

## QA Test Responses

When returning QA test steps in a response, always include:
- Pastable Postman request examples (method, URL, headers, body).
- Copy-pasteable cURL examples for fast terminal testing.
- Database change queries needed for setup and verification.

QA documentation should be formatted so it can be pasted directly into a GitHub issue. Use clear sections such as:
- Preconditions.
- Setup SQL.
- Postman request.
- cURL request.
- Expected response.
- Verification SQL.
- Cleanup SQL, when useful.

Postman examples must include:
- HTTP method.
- URL using `{{base_url}}`.
- Required headers.
- JSON body when applicable.
- Expected HTTP status and relevant response body fields.

cURL examples must be directly runnable. Prefer localhost URLs for fast local testing and environment variables for secrets, for example `Authorization: Bearer ${CLIENT_TOKEN}`. Omit authentication headers for unauthenticated endpoints.

Database queries must cover setup and verification. For date/time flows, explicitly state timezone expectations, including whether the request uses pro-local datetime or UTC datetime.

When documenting error cases, include one request per expected failure and specify the expected status code and error code/body.

## README Architecture Summary

Keep `README.md` short and only include necessary architectural information. Update it when architectural choices change or new ones are introduced.

Add or update the README when:
- A new domain is created. Include whether it uses full DDD or DDD-lite.
- Partial or full hexagonal (ports and adapters) patterns are introduced in a domain. Note this briefly.
