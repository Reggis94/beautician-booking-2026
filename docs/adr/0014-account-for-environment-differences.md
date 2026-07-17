# ADR 0014: Account for Environment Differences

## Context
Some routes and services intentionally exist only in specific environments.
For example, the ProAdmin demo dashboard is rendered in documentation and
production contexts where the development-only `app_logout` route is not
registered.

## Decision
Code and templates must not assume that development-only routes, services, or
configuration are available in every environment.

When a template or component can be rendered in multiple environments, it must
receive environment-specific URLs or dependencies from the caller, or use a
local fallback that does not require missing routes or services.

## Consequences
- Demo templates remain renderable in documentation and production contexts.
- Environment-specific behavior is explicit at the boundary that provides data
  to the template or component.
- Future changes that introduce routes, services, or configuration for only one
  environment must include a fallback or guard for other environments.
