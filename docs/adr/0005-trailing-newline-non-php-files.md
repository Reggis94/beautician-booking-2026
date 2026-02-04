# ADR 0005: Trailing Newline in Non-PHP Files

## Context
Many tools and editors expect text files to end with a newline. Missing trailing newlines can create noisy diffs and inconsistent formatting across environments.

## Decision
For all non-PHP files, ensure there is exactly one trailing newline at the end of the file.

## Consequences
- Non-PHP files should end with a single newline character.
- Editors and formatters should be configured to enforce this where possible.
