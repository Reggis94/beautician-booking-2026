# ADR 0012: Prefer a Single Configuration Source

## Context
Configuration lookups that try several variables for the same value can hide mistakes and make behavior harder to reason about. A typo, missing variable, or unexpected server state may silently switch execution to another source instead of failing clearly.

In some cases, an intentional fallback is acceptable when it is explicitly chosen, low risk, and documented as part of the behavior.

## Decision
For a given configuration value, use a single configuration source by default.

Only introduce a fallback when that fallback is intentional, does not create ambiguity, and clearly avoids a practical bug or operational problem.

## Consequences
- Configuration remains easier to understand because each value has one expected source.
- Misconfiguration is more visible instead of being masked by alternate lookups.
- Fallback chains must be justified rather than added by default.
