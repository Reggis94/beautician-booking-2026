# Symfony Certification Questions

## SYMFONY-QUESTION-0001: Can a route `env` parameter target multiple environments?

Question found while reviewing `#[Route('/login', name: 'login', env: 'dev')]`.

In Symfony 7.3, `Symfony\Component\Routing\Attribute\Route` defines `env` as `?string`, not as an array. A single route attribute can therefore target one environment only.

If the same route should exist in several environments, use one of these approaches:
- Omit `env` when the route should be available in all environments.
- Define environment-specific routing configuration/imports.
- Add separate route definitions for each environment when the behavior truly differs.

Do not pass an array such as `env: ['dev', 'test']`; it does not match the installed Symfony 7.3 route attribute signature.
