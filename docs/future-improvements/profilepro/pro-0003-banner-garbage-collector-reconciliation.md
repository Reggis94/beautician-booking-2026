# Pro 0003 - Banner Garbage Collector Reconciliation

`TO-PRO-0003`

## Context
The banner upload flow intentionally tolerates temporary mismatches between filesystem commits and DB rows.
Today, reconciliation is manual (support intervention), and no automatic garbage collector exists.

## Follow-ups
- Add a GC process for ProfilePro banner commits.
- Remove commit directories that are no longer referenced by DB.
- Flag or remove DB commit references whose folder does not exist.
- Optionally prune obsolete older commits while keeping the latest valid commit.
- Define safe execution strategy (dry-run mode, logs, and idempotent behavior).
- Remove `TO-PRO-0003` comments after implementation is complete.
