# Banner Upload & Read Consistency Model

## Context
Banner uploads write to two different systems:
- Storage (banner files by commit folder/key)
- Database (banner metadata, ordering, active/inactive state)

These writes are not a distributed transaction. Temporary mismatch between storage and database is expected and accepted.

The system needs a strict rule for read eligibility that stays deterministic even during failures and concurrency.

## Decision
1. The database is the source of truth for read eligibility.
2. Files may be published before the database transaction commits.
3. A commit is readable only when its full row set is committed in the database and all rows for that commit are active (`deleted_at IS NULL`).
4. Storage folders alone must never determine readability.
5. Temporary storage/database mismatches are expected and accepted.
6. Orphaned storage commits are reconciled later by support or a dedicated reconciliation job (garbage collector).
7. Concurrent uploads may publish multiple storage commits, but only one commit can become active in the database.
8. Reads always select the most recent commit that is fully active in the database.

## Fault Tolerance Rationale
This model prioritizes read correctness and predictable visibility over cross-system atomicity.

Rationale:
1. Database-gated reads prevent exposure of partially failed uploads.
2. Publishing files before DB commit keeps the write path simple and synchronous for v1 operations.
3. Accepting temporary mismatch shifts recovery to reconciliation, which is operationally simpler than distributed transactions.
4. A single authority (database) avoids ambiguous behavior during incidents and concurrency races.

Trade-off:
- The system accepts short-lived drift between storage and database and requires monitoring/support or GC reconciliation.

## Read Model
Read selection is database-gated.

For a given `pro_id`:
1. Resolve candidate commits from database records only, ordered by database-defined recency (newest first).
2. A commit is eligible only if:
- Its rows are fully persisted (no partial commit state).
- Every row for that commit is active (`deleted_at IS NULL`).
3. Select the first eligible commit and return banners ordered from database fields (for example, `order_number`).
4. Never scan storage folders to choose an active commit.

Storage is a file-serving dependency, not an authority for commit eligibility.

## Write Model
High-level flow:
1. Stage upload files.
2. Publish files to final storage commit location.
3. Execute database transaction that persists the commit rows and applies active/inactive transitions.

Important rule: publish-to-storage can happen before DB commit. Therefore, storage can contain a commit that is never readable.

## Failure Scenarios
Allowed inconsistency states:
1. Storage published, DB transaction fails.
- Result: orphaned storage commit.
- Read impact: not readable because no active DB commit rows exist.
- Resolution: reconciliation job or support cleanup.
2. DB commit succeeds, storage becomes unavailable or is later missing/corrupted.
- Result: DB still points to an active commit.
- Read eligibility: still determined by DB (commit remains selected).
- Operational impact: asset delivery may fail until repaired.
- Resolution: reconciliation/repair workflow.
3. Upload fails before DB transaction starts.
- Result: possible staged or temporary files only.
- Read impact: none; previous DB-active commit remains authoritative.

Explicitly disallowed interpretation:
- "Folder exists, so it is readable." This is invalid.

## Concurrency Behavior
Concurrent requests can each publish distinct storage commits.

Database commit activation remains the gate:
1. Multiple published folders may coexist.
2. Only commits that finish with fully active DB rows can be read.
3. If multiple uploads race, reads return the most recent fully active DB commit.
4. Non-winning published folders are treated as orphaned/obsolete storage and cleaned later.

## Consequences
- Deterministic reads: commit eligibility is stable because one authority (DB) decides visibility.
- Better failure tolerance: filesystem-first publish does not leak partially failed uploads to readers.
- Operational burden is explicit: reconciliation is required for orphaned storage and drift.
- Monitoring should track drift (DB-active commit with missing assets, storage commit without DB reference).

## Alternatives Considered
1. Storage-first read resolution ("pick newest folder on disk").
- Rejected: violates database authority and can expose uncommitted/invalid uploads.
2. Strict two-phase commit across DB and storage.
- Rejected for now: complexity and operational overhead are not justified for current scope.
3. Automatic inline rollback/repair during upload request.
- Rejected for now: increases write-path complexity and failure coupling; reconciliation job is simpler and safer.
