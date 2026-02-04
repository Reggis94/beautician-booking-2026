# Agent Instructions

## Documentation Use

Always consult `README.md` when making or reviewing changes, to ensure alignment with current architecture guidance and conventions.
When a future improvement in `docs/future-improvements` is implemented, delete its file to keep the backlog accurate.

## Coding Standard

All PHP code must be PSR-12 compliant.

## ADR Usage

Follow the ADRs in `docs/adr` as much as possible.

## README Architecture Summary

Keep `README.md` short and only include necessary architectural information. Update it when architectural choices change or new ones are introduced.

Add or update the README when:
- A new domain is created. Include whether it uses full DDD or DDD-lite.
- Partial or full hexagonal (ports and adapters) patterns are introduced in a domain. Note this briefly.
