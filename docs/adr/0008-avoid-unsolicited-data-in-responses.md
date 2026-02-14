# ADR 0008: Avoid Unsolicited Data in Write Responses

## Context
Write endpoints were returning database identifiers after creating records. These identifiers are not required by the client flow and expose internal database data without explicit need.

## Decision
Write endpoints must not return unsolicited data from the database (for example, auto-increment IDs). Successful creation should return only an appropriate HTTP status, with no payload unless explicitly required by a documented contract.

## Consequences
- Create flows return empty `201 Created` responses.
- Repository and handler APIs no longer return identifiers solely for response payloads.
