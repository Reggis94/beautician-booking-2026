# ADR 0012: ServiceCategory Owns Service Category Code

## Context
Service categories describe how a professional groups offered services. They do not currently carry independent business rules outside the services capability.

## Decision
Model service category behavior inside the existing `Services` domain `ServiceCategory` feature. Category-specific application code belongs under `src/Services/Application/ServiceCategory`, HTTP controllers under `src/Services/UI/Http/Controller`.

Since `Services` became full DDD (ADR 0022), a category is a `ServiceCategory` entity of the `Services` domain, persisted through `ServiceCategoryRepositoryInterface`. It is not a separate domain: category rules (renaming and deleting with appointment history) are applied together with the service rules, in the same transactions.

## Consequences
- Service category code follows the Services bounded context.
- Category HTTP routes may keep their existing public URLs when the API contract does not change.
- Future category behavior should extend `ServiceCategory` unless independent invariants emerge.
