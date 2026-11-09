# ADR 0008: Avoid Unsolicited Data in Responses

## Context
Write endpoints were returning database identifiers after creating records. These identifiers are not required by the client flow and expose internal database data without explicit need.

Some endpoints also returned exception messages to users. Even with narrowly caught, custom exceptions, a message can change over time or be thrown with unexpected content, and exposing it risks leaking internal details unintentionally.

## Decision
Write endpoints must not return unsolicited data from the database (for example, auto-increment IDs). Successful creation should return only an appropriate HTTP status, with no payload unless explicitly required by a documented contract.

Exception messages and constraint violations must never be included in responses. Controllers catch the specific exceptions they expect and return only the matching HTTP status, with an empty body. The status is documented on the endpoint so clients can react to it.

This applies to responses built by application code. Request mapping failures (ADR 0009) are rendered by Symfony, which includes the constraint violations; no extra layer is added to hide them.

## Consequences
- Create flows return empty `201 Created` responses.
- Repository and handler APIs no longer return identifiers solely for response payloads.
- Error responses carry only an HTTP status; each controller documents the statuses it can return.
- Clients distinguish failures by status code and endpoint, not by message text.
- Controllers avoid catching broad exception types.
- Existing endpoints outside the domains being changed may still return messages until they are touched.
