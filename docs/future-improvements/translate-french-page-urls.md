# Translate the Remaining French Page URLs

## Context
ADR 0021 requires French pages to use French URLs under `/fr`. The login pages follow it (`/fr/connexion`,
`/fr/connexion/confirmation`). These French pages still have English path segments:

| Route name | Current path | Suggested path |
| --- | --- | --- |
| `pro_admin_appointments_upcoming_fr` | `/fr/pro/dashboard/upcoming` | `/fr/pro/tableau-de-bord/a-venir` |
| `demo_pro_admin_dashboard_fr` | `/fr/demo/pro/dashboard` | `/fr/demo/pro/tableau-de-bord` |
| `demo_pro_admin_calendar_fr` | `/fr/demo/pro/dashboard/calendrier` | `/fr/demo/pro/tableau-de-bord/calendrier` |
| `demo_pro_admin_appointments_upcoming_fr` | `/fr/demo/pro/dashboard/upcoming` | `/fr/demo/pro/tableau-de-bord/a-venir` |
| `demo_pro_admin_appointments_past_fr` | `/fr/demo/pro/dashboard/past` | `/fr/demo/pro/tableau-de-bord/passes` |
| `demo_pro_admin_services_index_fr` | `/fr/demo/pro/dashboard/services` | `/fr/demo/pro/tableau-de-bord/prestations` |
| `demo_pro_admin_opening_hours_fr` | `/fr/demo/pro/dashboard/opening-hours` | `/fr/demo/pro/tableau-de-bord/horaires` |

`/fr/demo/pro` and `/fr/demo/pro/{username}` can stay: "demo" and "pro" are understood in French.

## Improvement
Rename these paths (route names stay the same, so generated links follow automatically). Check that the new paths
do not collide with `/fr/demo/pro/{username}`. Redirect the old demo paths to the new ones, since demo links have
been shared with customers.

## Reason
The URL a user sees, shares or bookmarks should match the page language (ADR 0021).
