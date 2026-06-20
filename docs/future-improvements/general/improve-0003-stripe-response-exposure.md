# Stripe Response Exposure

Source markers use `TO-IMPROVE-0003`.

Some Stripe-facing endpoints currently expose upstream Stripe error response bodies, exception messages, or Stripe-derived success response data directly to the frontend.

## Follow-ups
- Return generic error messages in production while preserving useful Stripe details in non-production environments.
- Log or monitor the original Stripe error details server-side before returning generic production responses.
- Return only the Stripe-derived fields that the frontend needs, and avoid forwarding raw Stripe response objects or unnecessary upstream data.
