# ADR 0022: Versioned Service Catalog in a Full-DDD Services Domain

## Context
Appointments reference the service they were booked with. Professionals now edit, publish, delete and
illustrate their services and categories, and these changes must never alter what existing appointments
show (name, description, price, duration, category), including future, cancelled and soft-deleted ones.
These rules span several records (a category, its services, their images and files) and must hold under
concurrent edits and bookings. They no longer fit DDD-lite DAOs (ADR 0004, ADR 0015).

## Decision
`Services` is a full-DDD domain: `Domain` (entities, value objects, domain services, repository
interfaces), `Application` (commands, queries, handlers, ports), `Infrastructure` (DBAL SQL repositories,
local file storage) and `UI`. Repositories use explicit DBAL SQL, never the ORM.

Lifecycle:
- A service is a draft (no `published_at`), published, or deleted (`deleted_at`, whatever `published_at`).
- A category has no publication state: it is active when it has at least one published, not deleted service.
- A version with appointment history (any appointment, whatever its state) is never overwritten. Changing
  its content (name, description, price, duration, category) creates a replacement: the old row gets
  `deleted_at` and `deletion_reason = 'replaced'`, the new row gets `previous_version_id`. Without
  appointment history the row is updated in place. `ServiceNewVersionDecision` holds this rule.
- Renaming a category with appointment history replaces it; its booked services are replaced too, the
  others move to the new version. Deleting a category either deletes its services or keeps them without a
  category (booked services through a replacement).
- An explicit deletion has `deletion_reason = 'deleted'`, distinct from a replacement.
- An old version (one that `previous_version_id` points to) always has `deleted_at`. Database triggers on
  `service` and `service_category` enforce it, so a new version must be saved after its old version is retired.
- "Not deleted" means `deleted_at IS NULL`: neither explicitly deleted nor replaced. For a service, it covers
  both drafts and published services.
- Publication and image changes never create a version.
- Appointments keep their service version; the category of an appointment is the category of that version,
  which never changes once booked.

Images:
- At most two active images per service, 5 MB each, JPEG, PNG or WebP (validated from the content).
- One `service_image` row per file (`service_id`, `file_name`, `created_at`, `deleted_at`). File names are
  random and unique across all professionals.
- Images are not versioned: when a service is replaced, its active images move to the new version
  (`service_id` is updated). Older versions keep no images.
- Active files are stored in the external upload storage under `services/<pro-id>/`, one folder per
  professional, none per service or version. Removing an image soft-deletes its row and moves the file to the
  shared `services/deleted/` folder. Files are never removed from disk.
- File writes and moves happen before the database commit and are undone (moved aside, never removed) when
  the transaction fails. The storage is a port (`ServiceImageStorageInterface`, partial hexagonal).

Concurrency: every catalog write runs in one transaction that first locks the professional's `pro` row, the
lock new bookings also take. Bookings, versioning decisions and image limits therefore never interleave.
Bookings are only accepted for published, not deleted service versions.

## Consequences
- ADR 0004 lists `Services` as full DDD; ADR 0012 describes categories as entities of that domain.
- Historical versions stay in the `service` and `service_category` tables and are hidden from listings.
- Public pages list only published, not deleted services and categories that contain one.
- Ordering is alphabetical in French, computed in PHP (`FrenchNameOrder`, without the intl extension),
  with ids as the final tie-breaker. There is no manual ordering.
