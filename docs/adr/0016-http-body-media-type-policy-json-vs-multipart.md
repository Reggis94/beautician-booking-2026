# ADR 0016: HTTP Body Media Type Policy (JSON vs Multipart)

## Context
HTTP write endpoints currently do not enforce a consistent request `Content-Type` policy across use cases.

Some operations upload files (for example, `ProfilePro` banner image upload), while most write operations submit structured data only. Without a clear rule, clients may send ambiguous or incorrect media types, and server-side parsing behavior becomes less explicit.

## Decision
Define a single policy for HTTP request bodies on write endpoints:

- If the endpoint uploads files, it must accept only `multipart/form-data`.
- If the endpoint does not upload files, it must accept only `application/json`.

For `ProfilePro` banner upload (`POST /api/pro/banner/create/upload-image`), the controller now rejects non-`multipart/form-data` requests with HTTP `415 Unsupported Media Type`.

## Consequences
- API contracts become explicit and predictable for clients.
- File-upload parsing paths remain isolated to multipart endpoints.
- Non-upload write endpoints remain JSON-first, with simpler validation and mapping.
- Clients sending the wrong media type must update request headers/body format.
