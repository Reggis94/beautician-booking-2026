# ADR 0011: GET Endpoints Read Input From Query Parameters

## Context
Some GET endpoints accepted input from the request body. For HTTP GET, request input should be provided in the URL query string to keep behavior explicit and interoperable across clients, proxies, and caching layers.

## Decision
GET endpoints must read client-provided input from query parameters, not from the request body.

For `ProfilePro` geocoding (`/api/pro/timezone-location/geocode-address`), the controller now reads the address from query parameters (`full_address`, `fullAddress`, or `address`) only.

## Consequences
- GET request handling is aligned with HTTP expectations for URL-based input.
- Client usage is clearer: inputs are visible in request URLs and easier to test/debug.
- Existing clients that previously sent GET bodies must move those fields to query parameters.
