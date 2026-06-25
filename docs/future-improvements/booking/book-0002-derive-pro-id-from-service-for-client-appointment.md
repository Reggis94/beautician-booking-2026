# Booking 0002 - Derive Pro ID From Service For Client Appointment

## Context
The public client appointment creation flow currently requires clients to submit both `proId` and `serviceId`.

The selected service already belongs to one professional profile. The request also carries the client appointment details, so `proId` is duplicate input at the request boundary.

## Improvement
Stop requiring `proId` in the client appointment creation request.

Possible implementation:
- Derive the professional profile ID from `serviceId` before appointment validation.
- Keep the existing service ownership guard until the derived flow replaces submitted `proId`.
- Use the derived professional profile ID for timezone lookup, business-time validation, appointment locking, overlap checks, persistence, and checkout metadata.
- Update the plain appointment creation endpoint and checkout-session appointment creation endpoint together.

## Reason
This avoids trusting duplicate client-submitted professional profile input when the selected service already identifies the professional profile for the booking flow.
