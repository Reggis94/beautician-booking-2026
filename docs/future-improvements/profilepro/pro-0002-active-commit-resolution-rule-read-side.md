# Pro 0002 - Active Commit Resolution Rule (Read-Side Contract)

`TO-PRO-0002`

## Context
Banner write-side stores `commit_id` and `order_number` in `pro_banner`, and filesystem folders are commit-based (see at `docs/profilepro/banner-storage-contract.md` for commit-level upload definition and rationale).
Read-side should resolve one active commit defensively to avoid broken states when DB and filesystem are temporarily inconsistent.
`deleted_at` must be interpreted at commit level for selection:
- a commit is active only when all rows in that commit have `deleted_at IS NULL`;
- if at least one row has `deleted_at IS NOT NULL`, that commit is inactive and must be skipped.
- only the most recent commit that satisfies this rule is eligible for read-side selection.

Source-of-truth rule:
- Read eligibility is database-gated.
- Storage folder presence must not decide active commit selection.

## Follow-ups
- Implement a dedicated read-side resolver in the ProfilePro banner read flow.
- Resolve banners for a `proId` by scanning DB commits from most recent to oldest and selecting the first fully active commit.
- Skip commits that are inactive by commit-level `deleted_at` rule.
- Ensure banner list is sourced from DB rows of the resolved active commit and ordered by `order_number`.
- Keep filesystem usage limited to asset serving and reconciliation/monitoring checks (not commit eligibility).
- Remove `TO-PRO-0002` comments after implementation is complete.
