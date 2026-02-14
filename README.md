# Beautician Booking 2026

## AI Collaboration Notes

`AGENTS.md` defines how AI assistants should operate in this repo to maximize productivity and quality. It requires consulting this README for architecture context and following ADRs in `docs/adr` as the source of architectural decisions, while updating those records when new decisions are made. In practice: AI work starts here for context, aligns with existing ADRs, create new ADRs and updates README when architecture changes.

## Coding Standard

All PHP code must be PSR-12 compliant.

## Transitioning to DDD

This project is transitioning from a conventional layered structure to Domain-Driven Design (DDD). The goal is to make business rules around availability explicit, resilient, and easier to change as the product evolves. DDD keeps domain logic close to the language of the business and reduces coupling to framework and delivery concerns, which improves testability and maintainability.

## DDD Scope (Full vs Lite)

Some domains have a dedicated `Domain` layer, others do not. We use full DDD only where invariants are heavy (entities + repositories + explicit domain layer). For simpler domains we use DDD-lite: domain logic stays in the `Application` layer and uses DAOs instead of repositories. This balance (along with partial hexagonal patterns) is intentional to avoid over-engineering.

Full DDD:
- `Availability`

DDD-lite:
- `ProfilePro`
- `Services`
- `Booking`
- `Identity`

## Partial Hexagonal Architecture (ProfilePro)

ProfilePro applies a partial hexagonal (ports and adapters) approach. For example, the application defines a `TimezoneResolverPortInterface` and provides a `TimezoneDbAdapter` for the TimezoneDb API. This keeps the core logic independent from a specific provider and makes it possible to swap to another timezone API with minimal change.

## Architecture Decision Records (ADRs)

This project includes Architecture Decision Records under `docs/adr`:
- 0001: Consistent Validation Error Format
- 0002: Prefer Single-Type Parameters and Properties
- 0003: PSR-12 Coding Standard
- 0004: DDD Scope (Full vs DDD-lite)
- 0005: Trailing Newline in Non-PHP Files
- 0006: Application Interfaces for External Concrete Classes
- 0007: Remove Implemented Future-Improvement Docs
- 0008: Avoid Unsolicited Data in Write Responses
- 0009: DTO Validation and Command Handlers for Writes
- 0010: Monitor Comment ID Matches Monitor Doc ID
- 0011: GET Endpoints Read Input From Query Parameters
