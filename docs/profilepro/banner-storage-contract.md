# Banner Storage Contract

## Status
Intentional design trade-offs accepted.

This document defines the current operational contract for banner uploads, storage, publishing, and reading.

The system is intentionally optimized for a happy-path-first strategy.
Temporary inconsistencies are tolerated and resolved later by support or a future garbage collector (GC).

## 1. Source of Truth

### 1.1 Order Source of Truth
The database is the source of truth for banner order.

Order is defined per commit ID.

The filesystem does not define banner order.

Order numbers are stored in DB rows associated with a specific commit ID.

### 1.2 Active Commit Resolution Rule (Read-Side Contract)
When resolving banners for a `proId`, the reader MUST:

1. Fetch commit IDs for that pro ordered by most recent first.
2. For each commit:
   - Check whether the corresponding commit directory exists in the permanent storage.
   - The first commit whose directory exists is considered the active one.
3. If none exist:
   - Return no banners (or defined fallback behavior).

This rule guarantees safe behavior even if:
- DB references a commit whose folder does not exist.
- A folder exists but the DB was not written.

## 2. Publish Flow

### 2.1 High-Level Flow
Banner upload follows this sequence:

1. Stage files in a staging directory.
2. Publish staged files to a permanent commit directory.
3. Write database records as the last step.

Database writes are intentionally performed last.

### 2.2 Failure Tolerance Model
We explicitly tolerate the following mismatches:

Case A - Files exist but DB write failed
- The commit directory exists.
- No DB rows reference it.
- Result: banners are not visible.
- Resolution: support or GC fixes DB state.
- This is acceptable.

Case B - DB rows exist but folder missing
- Reader will skip that commit.
- Reader will fallback to the previous valid commit.
- System remains functional.
- This is acceptable.

## 3. Commit Directory Rules

### 3.1 Commit ID
Commit ID is generated per upload operation.

It must be unique.

It must be filesystem-safe.

### 3.2 Duplicate Commit Directory
If the final commit directory already exists:

The system MUST fail hard.

No overwrite.

No auto-merge.

No auto-repair.

Support will handle resolution if this occurs.

## 4. Staging Model
Files are first copied to a staging directory.

Files are moved into a temporary `publishing-{commitId}` directory.

A final atomic rename promotes it to `{commitId}`.

This ensures:
- No partially published final directories.
- Either full commit directory exists or not.

## 5. Database Contract
Each banner DB row must contain:

- `proId`
- `orderNumber`
- `finalKey`
- `commitId`

Order numbers:
- Must be unique per commit.
- Must be between 1 and 20.
- Maximum 20 banners per commit.

## 6. Concurrency Model
Current assumption:
- No async workers.
- No long-running PHP processes.
- Classic request-response lifecycle.

Concurrency handling:
- Commit IDs isolate uploads.
- "Last valid commit on disk" resolution ensures deterministic read behavior.

## 7. Inconsistency Policy
We intentionally do not:
- Roll back filesystem changes on DB failure.
- Roll back DB on filesystem publish failure (beyond transaction scope).
- Attempt automatic reconciliation during upload.

Instead:
- The read side is defensive.
- Support may intervene manually.
- A future garbage collector will harmonize:
  - Orphan folders
  - Orphan DB rows
  - Obsolete commits

## 8. Garbage Collector (Future Responsibility)
The future GC is expected to:
- Remove commit directories not referenced by DB.
- Remove DB commits whose folders do not exist.
- Optionally prune older commits, keeping only the latest valid one.
- Harmonize mismatches.

The upload flow does not perform cleanup.

## 9. Explicit Non-Goals (For Now)
- Strong transactional consistency between DB and filesystem.
- Automatic self-healing during upload.
- Multi-phase distributed commit logic.
- Filesystem-based ordering.

These may evolve later.

## 10. Guiding Principle
Trust the happy path.
Allow temporary inconsistency.
Let the reader be defensive.
Let GC reconcile later.

This document defines the current contract and must be respected by:
- Developers
- Future refactors
- AI-assisted modifications
- Maintenance scripts

Any deviation from this contract must update this document accordingly.
