# ADR 0012: Prefer Timestamp Lifecycle Fields Over Creation/Deletion Booleans

## Context
Tables need consistent lifecycle tracking and auditability. Boolean flags like `is_created` and `is_deleted` lose important timing information and introduce extra states that are less expressive than time-based fields.

## Decision
All new tables must include `created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()`.

When records can be soft-deleted, prefer `deleted_at TIMESTAMPTZ` over boolean lifecycle flags (for example, `is_deleted` or `is_created`).

Use boolean fields only when they represent business state rather than lifecycle metadata.

## Consequences
- Creation and deletion moments are captured precisely.
- Soft-delete behavior is standardized through `deleted_at`.
- Schema design avoids redundant lifecycle booleans and improves query clarity.
