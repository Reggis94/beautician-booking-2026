# ProAdmin Demo Environment

The ProAdmin screens that currently use fake data are an isolated customer demo
environment. They exist so potential customers can test the professional
administration experience before every screen is connected to backend routes.

## Scope

Demo templates may use fake data, local state, and demo-only routes. Demo route
paths must include the `/demo` prefix, for example
`/demo/pro/dashboard`. They must not be treated as the production frontend
contract. Their purpose is to keep the ProAdmin user experience reviewable by
potential customers while backend integration is still evolving.

Equivalent non-demo templates should be built later for the real application.
Those real templates will work with backend routes and API contracts instead of
demo data.

## Demo behavior

The demo environment should have a life of its own:

- Links, buttons, and forms in demo templates should stay inside valid demo
  flows.
- Demo routes should be named as demo routes and should use paths under
  `/demo/...` so they cannot be confused with real application routes.
- A demo interaction should not submit to a missing route or navigate to a page
  that ends in an application error.
- When the real backend behavior is not implemented yet, the demo should use a
  harmless demo response, local UI transition, or reachable placeholder demo
  route.
- Demo interactions should be safe for potential customers to click through
  without requiring real authentication, real payments, or real data writes.
- The demo public frontend lives at `/demo/pro`, uses fake presentation data,
  and links back to `/demo/pro/dashboard`.
- Demo frontend banners are stored in the `pro-banners/demo` folder and served
  through the demo-only `/demo/pro-banners/{bannerFile}` route. Numeric banner
  folders and `/pro-banners/{proId}/{bannerFile}` remain reserved for real
  professional records.
- Logout must remain visible and clickable in the demo because it is part of
  the real ProAdmin navigation, but it should be a no-op. It must not call the
  real logout route, clear authentication, or send the customer out of the demo.

## Relationship to real templates

Demo templates and real templates must stay visually and behaviorally aligned.
When a real ProAdmin template is introduced, the matching demo template should
be updated to reflect the same layout, wording, states, and interaction model.

The demo can keep fake data, but it should not become a separate product design.
It is a stable preview of the same ProAdmin experience that the real app will
serve through backend-connected routes.

## API contract precedence

For real, non-demo templates, the backend API contract takes precedence over any
frontend assumption made while building the demo. If the demo expected a request
or response shape that differs from the implemented backend contract, update the
frontend integration and the demo documentation to match the backend contract.

Frontend code should not preserve an invented demo request or response shape
once the backend contract exists.
