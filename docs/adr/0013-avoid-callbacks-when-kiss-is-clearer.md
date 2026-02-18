# ADR 0013: Avoid Callbacks When KISS Is Clearer

## Context
Some application and infrastructure flows became harder to read because simple procedural steps were wrapped in callback-based helper abstractions. In banner upload logic, this added indirection without clear business value.

## Decision
Prefer direct, explicit control flow over callback-driven helpers when both approaches solve the same problem.

In particular:
- Avoid introducing callbacks for straightforward file and collection operations.
- Use callbacks only when they are required by framework/library APIs or when they clearly reduce complexity.
- For banner upload code, keep staging, metadata extraction, cleanup, and file operations explicit and linear.

## Consequences
- Banner upload flow is easier to read and review.
- Error-handling paths are more obvious during staging and cleanup.
- Utility abstractions that hide simple steps behind callbacks are reduced.
