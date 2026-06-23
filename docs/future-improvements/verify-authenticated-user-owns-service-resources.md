# Verify Authenticated User Owns Service Resources

## Context
The professional service and service category endpoints accept `pro_id` from request input.

Some command handlers already verify that a service or service category belongs to the submitted professional profile ID. However, the request boundary still needs to verify that the authenticated user owns the professional profile, service, or service category being created, updated, or deleted.

## Improvement
Before executing professional service or service category commands, verify that the authenticated user is allowed to manage the targeted resource.

Possible implementation:
- Resolve the authenticated user from the security context.
- Resolve the professional profile owned by that user.
- Reject requests where the submitted `pro_id`, service, or service category does not belong to that authenticated user.
- Prefer deriving `pro_id` from the authenticated user where possible instead of trusting request input.

## Reason
This prevents one authenticated professional user from creating, updating, or deleting services and service categories for another professional profile by submitting another `pro_id`.
