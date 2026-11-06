---
paths:
  - 'docs/**'
---

# Docs

## Guide pages name roles, not permissions
In "Kas ką gali" and the rest of a guide page, say which role allows an action (the Lithuanian production name, e.g. „Komunikacijos koordinatorius“, „Studentų atstovų koordinatorius“), never a permission string like `duties.update.padalinys`. Permission strings appear only under "Techninė informacija". Check each named role against database/seeders/Role*Seeder.php; if a role the page names has no seeder, add one mirroring production so tests exercise the same role.

## Docs voice and shared glossary
Direct tu with imperatives (Pasirink, Įrašyk, Patikrink) belongs in step-by-step parts — Veiksmai, task procedures, a single instruction. Explanations (Kaip tai veikia, Kas ką gali, statuses, Susitarimai, overviews) mostly describe in neutral or impersonal Lithuanian („Užklausa pradedama…“, „galima atkurti“, „rodoma“), keeping tu where it reads naturally („tavo užduotys“). Don't open every sentence with a command; the app UI stays fully tu (lang.md), the docs are a calmer register. Follow .ai/rules/lang.md for the shared product glossary and copy actual interface labels. Distinguish pareigybė (position), pareigybės laikotarpis (one person's dated assignment), kadencija (institutional term), atsakomybė (what a duty does and the permissions it grants; a few carry a system purpose), koordinatorius and sekretorius; define shared concepts once and link them.
Write for a layperson: never "paskyrimas/paskyrimai" (say pareigos, pareigybės laikotarpis or pareigų datos), never "kandidatas"/"pretendentas" (say studentas or registracijos pateikėjas; for a release, "nauja versija"), and no dev jargon such as `main` or "release candidate" outside Techninė informacija.

## Guide claims follow supported behaviour
Verify named production roles against seeders AND policies/role tests; distinguish roles from actor relationships (e.g. teikėjas) and baseline access. Describe supported UI actions, not backend-only methods. Record creation does not imply invitations or notifications. Separate enforced rules, suggested defaults and organisational agreements; implementation identifiers belong only in Techninė informacija.

## Page types and authoritative structure
Keep existing workspace reference URLs and one authoritative explanation per behaviour. Task entry pages link to those references rather than duplicating matrices or date logic. Feature references use the section order below (guide layout); task procedures cover access, steps, result and recovery. Concepts and overviews need only relevant headings. Sidebar and PDF order remain in docs/.vitepress/structure.ts.

## Documentation status and actual review dates
Every LT guide page declares doc_status: draft (outline/unwritten), partial (some usable sections), or reviewed (the written page checked against current behaviour). Partial pages identify their covered scope and may cite tests for it; draft pages make no evidence claims for unwritten prose. last_reviewed is the actual content verification date, never automatically the edit/build/test date. Show it independently of tests; an overview's date does not certify its children. Do not add per-heading dates.

## Evidence and reading experience
Test citations identify existing files and support specific behaviour; they do not prove all prose. Keep technical details last, test evidence expandable on the web and complete in PDF. Wide tables scroll locally with keyboard access; verify phone/tablet/desktop, both themes and touch/keyboard. Keep anchors stable and ChangelogNote dates aligned with release dates; build web/PDF and check links after structural changes.

## Reusable author templates
Use docs/maintainers/authoring.md for reference, task and concept/overview templates. Use doc_status to communicate readiness.

## Links into the platform
Guide pages name app addresses as code (`` `/mano/notifications` ``); the PDF turns them into links. To make a clickable platform link on the web too, write `[Pranešimai](app:/mano/notifications)` — it opens in a new tab marked ↗. A plain `[…](/mano/…)` link resolves to the guide's own Mano section, not the platform.

## The guide's layout and PDF edition

The guide (`docs/`, Lithuanian only — `docs/en/` keeps just the changelog) mirrors `AdminNavigationCatalog`: one directory per workspace (`mano/`, `visak/`, `rezervacijos/`, `svetaine/`, `organizacija/`, `sistema/`) plus `pagrindai/` and `ivadas.md`. Its order lives only in `docs/.vitepress/structure.ts`; the sidebar and the PDF both walk it, so a new page is added there, not in `lt.ts`.

- A section page follows `rezervacijos/rezervacijos.md`: *Susitarimai · Kaip tai veikia · Veiksmai · Kas ką gali · Pranešimai ir automatizavimas · Techninė informacija* (plus *Rekomendacijos* when the page has them), with `doc_status`/`last_reviewed`/`tests` frontmatter. The main flow speaks in roles and plain Lithuanian; policy classes, permission strings and config keys go only in the closing `## Techninė informacija` section, which the "Įrodyta testais" block (web `TestEvidence.vue`, PDF `evidence.typ`) follows. Unwritten pages are `doc_status: draft`, carry a `::: warning Rašoma` callout and no `tests:`.
- `tests:` may cite Pest files (`tests/**.php`, shown as *Serveris*) and Vitest specs (`resources/js/**.test.ts`, *Sąsaja*) — `tests/Feature/Docs/DocTestCitationsTest.php` fails on anything else or on a file that no longer exists.
- `<ChangelogNote date="2026-10-26" title="…">` (blank lines around its markdown body) marks where a change shaped the section; the date is the changelog entry's, and the note links to that major's changelog (worked out from the date) on the web and in the PDF.
- `npm run docs:pdf` (`docs/pdf/build.ts` + `build-release.ts`, runs on the host: it needs `typst` and Node ≥ 22.18 for native `.ts`) → `docs/public/vusa-lt-gidas.pdf` and the newest major's release notes `vusa-lt-vN-atnaujinimai.pdf`, both gitignored; run it **before** `docs:build`, which copies them. `docs/pdf/markdown.ts` translates VitePress-only syntax for cmarker (`:::` → `<callout>`, `<DocScreenshot>` → `<screenshot>`, site links → PDF labels keyed on VitePress' slugger), so write plain VitePress markdown — don't add Typst to pages. In the guide a link to a missing page/anchor fails the Typst compile; the release PDF sends every guide link to `APP_URL/gidas` (deploy passes the environment's app URL, so a staging PDF links to staging).

## Frontmatter a page declares

Written by hand by whoever writes the prose, in the same file and commit.

```yaml
---
title: Rezervacijų sistema
doc_status: reviewed
last_reviewed: 2026-08-26               # when the prose was last checked against the app
tests:                                  # evidence: proven by these files
  - tests/Feature/Admin/Resources/ReservationControllerTest.php
---
```

- **Claims name test *files*, never `it()` names** — test names churn; a deleted or moved file is exactly what you want flagged.
- **`last_reviewed` is a plain date.** Quoted or unquoted both work.
