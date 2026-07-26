# ADR 0015: Prefer SQL DAOs for DDD-lite Data Access

## Context
Simple application flows often need to read or write only a small, use-case-specific
set of columns. Modeling these operations with ORM entities and domain repositories
adds object hydration and domain structure without providing useful invariants.

Hiding data-access calls inside helper methods also makes handler orchestration less
visible.

## Decision
DDD-lite domains use application-level DAO interfaces with infrastructure
implementations backed by explicit SQL through Doctrine DBAL.

DAO methods accept and return only the scalar values or shaped arrays required by
their use case. They must not introduce ORM entities merely to move database data
between a handler and a DAO.

Keep DAO interactions visible in application handlers when practical. A private
helper may contain data-access interactions only when the resulting handler code
would otherwise become substantially harder to read.

Full-DDD domains may continue to use entities and repositories where they protect
meaningful domain invariants.

## Consequences
- SQL queries and selected columns are explicit and reviewable.
- DDD-lite data paths avoid unnecessary entity hydration.
- Application handlers show their persistence orchestration directly.
- DAO APIs stay narrow instead of becoming generic table gateways.
