# ADR 0010: Monitor Comment ID Matches Monitor Doc ID

## Context
`TO-MONITOR` markers are used in source code to indicate where monitoring should be added later, and related backlog context is documented under `docs/future-improvements/monitoring`. Using different IDs for the same monitoring doc creates unnecessary fragmentation and makes traceability harder.

## Decision
Each monitoring backlog document defines a single `TO-MONITOR` identifier based on its file number. For example, `monitor-0001-*` uses `TO-MONITOR-0001`. All related source markers and documentation references for that backlog item must use that same identifier.

## Consequences
- Monitoring markers are consistently traceable to one backlog document ID.
- Searching for a `TO-MONITOR-*` ID reliably returns all related code and documentation entries.
- New monitoring references should reuse the document ID instead of introducing per-file IDs.
