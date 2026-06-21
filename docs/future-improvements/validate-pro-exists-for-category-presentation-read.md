# Validate Pro Exists for Category Presentation Read

## Context
`GET /api/pro-presentation/category/list` returns non-deleted categories for a given professional profile ID.

The current read filters categories by `service_category.pro_id` and checks `service_category.deleted_at IS NULL`,
but it does not verify that the owning `pro` row exists and is not deleted.

## Improvement
Before returning categories, ensure the requested professional profile exists, has not been soft-deleted, and remains
the professional profile that owns the returned categories.

Possible implementations:
- Join `pro` in the category repository query and require `pro.deleted_at IS NULL`.
- Or check `ProDaoInterface::existsById()` in the query handler before reading categories.

## Reason
This prevents category data from being returned for deleted or invalid professional profiles and makes the public presentation flow more complete.
