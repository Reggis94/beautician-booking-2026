# ADR 0013: Prefer Symfony Concepts Provided by Symfony Classes

## Context
This project uses Symfony as its delivery framework. When code avoids concepts already provided by Symfony classes without a clear reason, the result is often extra custom wiring, more configuration, and less predictable conventions for future maintenance.

Controllers are a common example. A plain invokable class can work, but when it bypasses Symfony controller conventions it may require extra service tags or configuration that `AbstractController` already solves cleanly.

## Decision
As much as possible, when a concept can be used from a Symfony class, prefer that Symfony-provided concept over a custom pattern in the Symfony delivery layer.

In controllers, prefer Symfony controller conventions such as extending `AbstractController` when that class already provides the concept needed with simpler wiring and clearer behavior.

Depart from Symfony conventions only when there is a concrete reason.

## Consequences
- Code should be easier to understand for Symfony developers because it follows familiar framework conventions.
- Custom patterns in Symfony-specific code now require a clearer justification instead of being the default.
