# Standardize Public Client Route Prefix

## Context
The public client flow currently uses both `/api/pro-presentation/...` and `/api/anonymous-client/...` route prefixes.

Presentation reads use `/api/pro-presentation/...`, while booking writes use `/api/anonymous-client/...`. Both belong to the same unauthenticated client-facing journey where a client views a professional presentation, selects a service, and creates an appointment.

## Improvement
Decide whether the public client flow should consistently use `anonymous-client` or `pro-presentation` in route paths and route names.

Possible implementation:
- Keep `pro-presentation` only for read-only presentation endpoints and document that boundary clearly.
- Or move the whole unauthenticated client journey under one shared prefix.
- If existing routes are renamed, provide a transition path for current clients.

## Reason
A homogeneous route naming convention makes the API easier to discover and reduces ambiguity when related endpoints are used together in the same public booking flow.
