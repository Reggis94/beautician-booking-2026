# ADR 0016: Avoid Arrow Functions for Readability

## Context
PHP arrow functions (`fn (...) => ...`) are concise, but that concision hurts
readability once the expression body grows past a trivial one-liner: array literals,
multi-line callbacks, and chained ternaries all become harder to scan when compressed
into a single implicit-return expression. They also implicitly capture the outer
scope, which can obscure what data a callback actually depends on.

## Decision
Avoid `fn (...) => ...` arrow functions. Prefer, in order of readability:
1. A plain `foreach` loop building a result array, when transforming a list.
2. A regular closure (`function (...) { ... }`, with explicit `use (...)` when needed)
   passed to `array_map`/`array_filter`/`usort`/etc., when a loop would be clearly
   less readable (e.g. a single call already scoped to library code expecting a
   callable).

This applies to new code and to code being modified. Existing arrow functions do not
need a dedicated cleanup pass.

## Consequences
- Callback bodies read top-to-bottom like the rest of the codebase instead of as
  compressed expressions.
- Closures make captured variables explicit via `use (...)` instead of relying on
  implicit scope capture.
- Some call sites become a few lines longer in exchange for clarity.
