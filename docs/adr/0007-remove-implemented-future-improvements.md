# ADR 0007: Remove Implemented Future-Improvement Docs

## Context
Future improvements are tracked under `docs/future-improvements`. Once the work is implemented, keeping the file creates false backlog noise and can mislead future work.

## Decision
When a future improvement is completed, delete its corresponding file in `docs/future-improvements`.

## Consequences
- The future-improvements folder remains an accurate backlog of pending work.
- Implementations must explicitly remove their completed improvement doc.
