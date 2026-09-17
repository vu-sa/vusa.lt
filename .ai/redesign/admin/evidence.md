# Evidence — what the data says

> **Admin redesign.** Read when: you are prioritising, or challenging an assumption.
> Index: [README.md](README.md) · Reps: [reps.md](reps.md)

## Evidence snapshot (2026-09-17)

Measured on the local database (a recent production copy — last meeting 2026-09-16).

**Roles** (holders, users + duties):

| Role | Holders |
|---|---|
| Studentų atstovas | 343 |
| Komunikacijos koordinatorius | 61 |
| Resursų administratorius | 39 |
| Studentų atstovų koordinatorius | 33 |
| PKP administratorius | 25 |
| Narių registracijos duomenų gavėjas | 23 |
| Centrinio biuro studentų atstovų koordinatorius | 20 |
| Archyvo dokumentų valdytojas | 19 |
| Susirinkimų / posėdžių administratorius | 15 |
| Super Admin / Pilnas administratorius | 3 / 1 |

**Reach** — distinct users with a page in their recently-visited list (264 users tracked; lists are
capped and `/mano` itself is excluded, so this is reach, not traffic):

| Path | Users | Entries |
|---|---|---|
| `/mano/administration` | **107** | 107 |
| `/mano/dashboard/atstovavimas` | 103 | 103 |
| `/mano/dashboard/reservations` | 101 | 101 |
| `/mano/profile` | 94 | 94 |
| `/mano/institutions/{id}` | 76 | 100 |
| `/mano/search` | 75 | 75 |
| `/mano/meetings/{id}` | 66 | 142 |
| `/mano/tasks` / `/mano/notifications` | 57 / 51 | — |
| `/mano/duties-update-users` | 45 | 45 |
| `/mano/users` | 44 | 44 |
| `/mano/agendaItems/{id}/edit` | 43 | **115** |
| `/mano/dashboard/svetaine` · `pages` · `news` · `calendar` | 22 · 14 · 11 · 17 | — |
| Users with any pinned page | **1** | — |

**Navigation drift** (2026-09-17): `studySets`, `mailQueue`, `supportRequests` and `tasks.summary`
appear in **none** of the sidebar, the Administravimas page or the palette; the palette knows ~12
destinations; the Administravimas page gates visibility on `create`, not `viewAny`.

What it says: "admin" is ~10 specialist roles needing a few sections each; the heaviest repeated
loop is rep work (institution → meeting → agenda item); the tools directory is how admins navigate
today; customisation is unused; website editing is a minority task.

---

## Evidence queries

```sql
-- Role holders
SELECT r.name, COUNT(DISTINCT mhr.model_id) AS holders
FROM roles r LEFT JOIN model_has_roles mhr ON mhr.role_id = r.id
GROUP BY r.name ORDER BY holders DESC;

-- Reach per admin path, from recently-visited lists
SELECT REGEXP_REPLACE(REGEXP_REPLACE(jt.url, '/[0-9A-Za-z]{26}', '/{id}'), '/[0-9]+(/|$)', '/{n}$1') AS path,
       COUNT(*) AS entries, COUNT(DISTINCT u.id) AS users
FROM users u,
     JSON_TABLE(u.ui_preferences, '$.recent_pages[*]' COLUMNS (url VARCHAR(255) PATH '$.url')) jt
GROUP BY path ORDER BY users DESC LIMIT 40;
```
