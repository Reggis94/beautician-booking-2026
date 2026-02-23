# Pro 0004 - Banner Subdomain Switch to Full DDD

`TO-PRO-0004`

## Context
ProfilePro currently uses DDD-lite. Banner upload logic has grown around commit-level invariants (see at `docs/profilepro/banner-storage-contract.md` for commit-level upload definition and rationale):
- Commit-level invariant means a rule that must hold for the full banner commit payload before persistence, not just for a single item.
- image count and order-number consistency
- unique and bounded order numbers
- commit-level activity and soft-delete semantics
- staging/publish/write flow consistency

Today, validation logic is split across DTO callbacks and command handler checks. This works, but duplicates rules and increases drift risk as behavior evolves.

## Why Move Banner to Full DDD
- Create a single invariant boundary for Banner rules.
- Reduce duplicated validation paths (controller/DTO/handler).
- Keep commit-level business language explicit (`BannerCommit`, commit activity policy, order invariants).
- Improve testability by validating aggregate behavior without HTTP/controller coupling.
- Make read-side fallback and write-side rules easier to evolve safely.

## Target Direction
- Introduce a Banner aggregate root (commit-scoped) and supporting value objects (for example, commit ID, order number, banner item set).
- Add an aggregate factory/named constructor that validates all Banner invariants once.
- Keep controller focused on transport mapping and handler orchestration only.
- Move duplicated cross-field checks out of DTO callbacks and handler branches into aggregate construction.

## Impacted Code (Current Markers)
- `src/ProfilePro/UI/Http/Controller/Banner/CreateUploadBannerImageController.php`
- `src/ProfilePro/Application/Banner/Dto/CreateUploadBannerImagesDto.php`
- `src/ProfilePro/Application/Banner/CommandHandler/CreateUploadBannerImagesCommandHandler.php`

These files now contain `TO-PRO-0004` comments where refactor should happen.

## Follow-ups
- Define Banner aggregate and value objects under a dedicated ProfilePro Banner domain layer.
- Add repository abstraction aligned with aggregate persistence and commit-level policies.
- Add unit tests for aggregate invariants and commit activity semantics.
- Remove `TO-PRO-0004` comments after migration is complete.
