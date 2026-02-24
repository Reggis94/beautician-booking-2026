# ADR 0014: Use Empty HTTP Responses for Bodyless Statuses

## Context
Some controllers returned `new JsonResponse(null, ...)` for successful outcomes where the API contract expects no response payload. This adds unnecessary JSON semantics to bodyless responses and can lead clients to incorrectly expect JSON for those statuses.

## Decision
When an endpoint must return no payload, controllers must return `Response` with only the HTTP status code, not `JsonResponse`.

Apply this consistently to statuses where the endpoint contract is bodyless, including:
- `201 Created` (when no response payload is part of the contract)
- `202 Accepted` (when no response payload is part of the contract)
- `204 No Content` (must be bodyless)
- `205 Reset Content` (must be bodyless)
- `304 Not Modified` (must be bodyless)

## Consequences
- Bodyless success responses are explicit and protocol-correct.
- Clients should rely on status codes (not JSON bodies) for bodyless outcomes.
- JSON responses remain for endpoints that intentionally return structured payloads (including error bodies).
