# ADR 0020: ProAuth Tests as Executable Requirements

## Context
ProAuth tests grew to cover many implementation details (handlers, fakes, controllers, DAOs) and became hard to
review. They did not make the business rules easy to find or check.

## Decision
ProAuth tests are the documentation of its business rules. Each test reads as one requirement:
- The test name states the requirement in business language, for example
  `another_request_after_59_seconds_is_refused`.
- The expected numbers (seconds, counts) are written out in the scenario. They are never derived from service
  parameters or constants shared with production code, so a rule change means deliberately updating the tests.
- A test covers one rule at the level where that rule is decided (for example `LoginLinkThrottle::isAllowed()` for
  throttling), and asserts only that rule's outcome.
- Boundaries are tested on both sides (just refused, exactly allowed).
- Tests that need SQL run against the test database, as the other database tests do.

`tests/ProAuth/Application/Throttling/LoginLinkThrottleTest.php` is the reference example.

## Consequences
- New ProAuth tests must document a requirement. Tests that only check how the code is wired or mirror its
  implementation are not added.
- Fewer, focused tests: a requirement already documented at the level where it is decided is not tested again
  through callers.
- Changing a ProAuth business rule requires updating the matching test names and numbers.
