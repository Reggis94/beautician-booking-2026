# Symfony Certification Questions

## SYMFONY-QUESTION-0001: Can a route `env` parameter target multiple environments?

Question found while reviewing `#[Route('/login', name: 'login', env: 'dev')]`.

In Symfony 7.3, `Symfony\Component\Routing\Attribute\Route` defines `env` as `?string`, not as an array. A single route attribute can therefore target one environment only.

If the same route should exist in several environments, use one of these approaches:
- Omit `env` when the route should be available in all environments.
- Define environment-specific routing configuration/imports.
- Add separate route definitions for each environment when the behavior truly differs.

Do not pass an array such as `env: ['dev', 'test']`; it does not match the installed Symfony 7.3 route attribute signature.

## SYMFONY-QUESTION-0002: Can Symfony inject a route parameter directly into a controller argument?

Given this route and controller:

```php
#[Route(
    '/demo/pro/{username}',
    name: 'demo_front_pro_home_specific_user',
    methods: ['GET']
)]
public function demoHomeSpecificUser(string $username): Response
{
    // ...
}
```

Yes. Symfony stores the value matched by the `{username}` route placeholder in the request attributes and passes it to the controller argument named `$username`.

For a request to `/demo/pro/john`, the value of `$username` is `john`. The controller argument name must match the route placeholder name.

## SYMFONY-QUESTION-0003: How can a controller read a route parameter from the request?

A controller can read the route parameter directly from the request attributes:

```php
public function demoHomeSpecificUser(Request $request): Response
{
    $username = $request->attributes->get('username');

    // ...
}
```

Direct controller argument injection is usually clearer when the route parameter is a required scalar value. Reading request attributes is useful when the controller needs dynamic access to routing data or other request attributes.

## SYMFONY-QUESTION-0004: How can a route restrict the accepted format of a parameter?

Use a route requirement whose key matches the placeholder name:

```php
#[Route(
    '/demo/pro/{username}',
    name: 'demo_front_pro_home_specific_user',
    requirements: ['username' => '[a-zA-Z0-9_-]+'],
    methods: ['GET']
)]
public function demoHomeSpecificUser(string $username): Response
{
    // ...
}
```

The requirement is a regular expression. If the URL value does not satisfy it, this route does not match; if no other route matches the request, Symfony returns a `404 Not Found` response.
