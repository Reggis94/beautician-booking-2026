# ADR 0008: Avoid Unsolicited Data in Responses

## Context
Write endpoints were returning database identifiers after creating records. These identifiers are not required by the client flow and expose internal database data without explicit need.

Some endpoints also return exception messages to users. Broad exception catches can accidentally expose internal details when unrelated code throws the same exception type.

## Decision
Write endpoints must not return unsolicited data from the database (for example, auto-increment IDs). Successful creation should return only an appropriate HTTP status, with no payload unless explicitly required by a documented contract.

Exception messages may be returned to users only for a small set of explicit, user-facing exceptions. Those exceptions must be specific, usually custom application or domain exceptions, so unrelated framework, infrastructure, or generic PHP exceptions are not exposed by the same catch block.

## Consequences
- Create flows return empty `201 Created` responses.
- Repository and handler APIs no longer return identifiers solely for response payloads.
- Controllers avoid catching broad exception types when returning the exception message in the response.
