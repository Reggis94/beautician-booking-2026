# Store 0001 - Acuity Checkout Port/Adapter And CQRS

## Context
`AcuityStoreCheckoutController` injects the concrete `Stripe\StripeClient` directly and builds the Stripe checkout session (line items, price data, success/cancel URLs) inline in the UI controller.

This does not have an application-level interface for the external Stripe class, which conflicts with ADR 0006. It also puts checkout business logic in the UI layer instead of an application command/query handler, which conflicts with ADR 0009 and the DDD-lite convention of keeping domain/application logic out of controllers.

## Improvement
Introduce a partial hexagonal (ports and adapters) structure for Acuity store checkout, following the ProfilePro `TimezoneResolverPortInterface` / `TimezoneDbAdapter` precedent, and move the checkout flow into a command handler.

Possible implementation:
- Define a `CheckoutSessionPortInterface` in `Store\Application` describing checkout session creation (e.g. `create(string $templateId, int $amountCents): CheckoutSessionResult`).
- Implement it with a `StripeCheckoutAdapter` in `Store\Infrastructure` that wraps `Stripe\StripeClient`.
- Add a command/handler (per ADR 0009) that validates the template exists and calls the port to create the session.
- Reduce `AcuityStoreCheckoutController` to dispatching the command and rendering the redirect view.

## Reason
This keeps the application layer independent of the Stripe SDK, makes the checkout flow testable without hitting Stripe, and aligns the Acuity store checkout with existing architecture conventions (ADR 0006, ADR 0009, README's Partial Hexagonal Architecture section).
