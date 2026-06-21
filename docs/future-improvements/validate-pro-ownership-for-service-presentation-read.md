# Validate Pro Ownership for Service Presentation Read

## Context
`GET /api/pro-presentation/service/list` returns non-deleted services for a given service category ID.

The current read checks that services are linked to a non-deleted category and professional profile, but the query does not receive the requested professional profile ID. Because of that, it cannot explicitly verify that the category being requested belongs to the professional profile currently being presented.

## Improvement
When the public presentation flow has the professional profile ID available, include it in the service list query and ensure both the requested category and every returned service belong to that professional profile.

Possible implementation:
- Add `proId` to `ListProPresentationServiceForCategoryQuery`.
- Pass `proId` to `ServiceDaoInterface::listProPresentationServiceForCategory()`.
- Require both `s.pro_id = :pro_id` and `sc.pro_id = :pro_id` in the DAO query.

## Reason
This prevents a category ID from another professional profile from being used in the public presentation service list.
