# ADR 0021: French Pages Use French URLs

## Context
Glam4Style serves French-speaking beauticians and their clients. French pages were first served under English
paths (for example `/fr/login`), so the address the user sees, shares or bookmarks does not match the page
language.

## Decision
A French page is served under the `/fr` prefix, directly after the base URL, and every visible part of its URL is
in French:
- Path segments are French words, lowercase, without accents, separated by hyphens, for example
  `/fr/connexion/confirmation` or `/fr/tableau-de-bord/a-venir`.
- Query parameter names a user can see are French too, for example `/fr/connexion?deconnexion=1`.
- Values that are not words stay as they are: slugs, IDs, dates (`/fr/demo/pro/{username}`).

Not covered by this rule:
- API paths (`/api/...`) and their parameters stay in English: they are not seen by users.
- Route names stay in English with the `_fr` suffix, for example `pro_auth_login_page_fr`, so code reads the same
  in both languages.
- Configuration that points to a French page must use its French URL, for example
  `AUTH_LOGIN_CONFIRM_URL=https://<host>/fr/connexion/confirmation`.

An English version of a page, when served, uses English URLs without a language prefix.

## Consequences
- The `/fr/connexion` (login) and `/fr/connexion/confirmation` (login link confirmation) pages follow this rule.
- New French pages must follow it. Existing French pages with English URL segments are listed in
  `docs/future-improvements/translate-french-page-urls.md` until renamed.
- Renaming a French URL breaks old links and bookmarks: add a redirect from the old URL when the page was already
  shared publicly.
