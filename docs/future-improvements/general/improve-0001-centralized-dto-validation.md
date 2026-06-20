# Centralized DTO Validation

Source markers use `TO-IMPROVE-0001`.

Several HTTP controllers manually map validation violations into JSON error responses after building request DTOs.

## Follow-ups
- Introduce a shared validation flow for request DTOs, either through request-to-DTO mapping or an event/listener.
- Keep Symfony Validator as the validation mechanism so DTO constraints remain the single source of validation rules.
- Preserve the existing validation error response format.
