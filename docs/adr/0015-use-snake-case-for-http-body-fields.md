# ADR 0015: Use Snake Case for HTTP Body Fields

## Context
Request body field naming is currently mixed across domains:
- `Booking` and `Services` mostly use snake_case payload keys (for example, `pro_id`, `service_id`, `start_dt`, `price_cents`).
- `Availability` includes camelCase payload keys (for example, `proId`, `startDateTime`), which reflects older endpoint design.
- Some `ProfilePro` write endpoints accept both styles for backward compatibility.

This inconsistency increases client confusion, creates extra alias-handling code in controllers, and makes API contracts harder to keep uniform.

## Decision
For HTTP request bodies (JSON and multipart/form-data), snake_case is the canonical field naming convention.

Rules:
- New endpoints must define body fields in snake_case.
- Existing endpoints being modified should prefer snake_case in docs, examples, and validation messages.
- CamelCase body aliases may be kept only for legacy compatibility and should be removed when clients are migrated.
- Internal PHP names (DTO/command properties) may remain camelCase; this ADR applies to external HTTP body field names.

## Consequences
- API contracts become consistent across domains.
- Controller mapping logic becomes simpler over time as legacy aliases are removed.
- Clients can rely on one stable convention for request payloads.
