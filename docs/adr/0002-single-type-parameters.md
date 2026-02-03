# ADR 0002: Prefer Single-Type Parameters and Properties

## Context
Mixed or multi-type parameters complicate validation, reading, and maintainability. They also increase the need for runtime branching and reduce the clarity of what inputs are expected.

## Decision
As much as possible, constructor parameters, function parameters, and property types should be a single type. Avoid mixed types. Union types are allowed only when they enable validation at the correct layer or concern, and the reason is explicitly documented near the API in question.

## Consequences
- APIs become more predictable and easier to validate.
- Callers should normalize or convert values before calling into the domain or application layers when feasible.
- Union types should be rare and explicitly documented near the API in question.
