---
paths:
  - 'docs/**'
---

# Docs

## Guide pages name roles, not permissions
In "Kas ką gali" and the rest of a guide page, say which role allows an action (the Lithuanian production name, e.g. „Komunikacijos koordinatorius“, „Studentų atstovų koordinatorius“), never a permission string like `duties.update.padalinys`. Permission strings appear only under "Techninė informacija". Check each named role against database/seeders/Role*Seeder.php; if a role the page names has no seeder, add one mirroring production so tests exercise the same role.

## Docs voice and shared glossary
Address the reader as tu with direct verbs (Pasirink, Įrašyk, Patikrink). Follow .ai/rules/lang.md for the shared product glossary and copy actual interface labels. Distinguish pareigybė (position), pareigybės laikotarpis (one person's dated assignment), kadencija (institutional term), rolė (access bundle), atsakomybė (area of coordination), koordinatorius and sekretorius; define shared concepts once and link them.
Write for a layperson: never "paskyrimas/paskyrimai" (say pareigos, pareigybės laikotarpis or pareigų datos), never "kandidatas"/"pretendentas" (say studentas or registracijos pateikėjas; for a release, "nauja versija"), and no dev jargon such as `main` or "release candidate" outside Techninė informacija.

## Guide claims follow supported behaviour
Verify named production roles against seeders AND policies/role tests; distinguish roles from actor relationships (e.g. teikėjas) and baseline access. Describe supported UI actions, not backend-only methods. Record creation does not imply invitations or notifications. Separate enforced rules, suggested defaults and organisational agreements; implementation identifiers belong only in Techninė informacija.

## Page types and authoritative structure
Keep existing workspace reference URLs and one authoritative explanation per behaviour. Task entry pages link to those references rather than duplicating matrices or date logic. Feature references use Susitarimai, Rekomendacijos, Kaip tai veikia, Veiksmai, Kas ką gali, Pranešimai ir automatizavimas, Techninė informacija; task procedures cover access, steps, result and recovery. Concepts and overviews need only relevant headings. Sidebar and PDF order remain in docs/.vitepress/structure.ts.

## Documentation status and actual review dates
Every LT guide page declares doc_status: draft (outline/unwritten), partial (some usable sections), or reviewed (the written page checked against current behaviour). Partial pages identify their covered scope and may cite tests for it; draft pages make no evidence claims for unwritten prose. This refines the older unwritten-page instruction in commands.md. last_reviewed is the actual content verification date, never automatically the edit/build/test date. Show it independently of tests; an overview's date does not certify its children. Do not add per-heading dates.

## Evidence and reading experience
Test citations identify existing files and support specific behaviour; they do not prove all prose, and docs:coverage drift is advisory. Keep technical details last, test evidence expandable on the web and complete in PDF. Wide tables scroll locally with keyboard access; verify phone/tablet/desktop, both themes and touch/keyboard. Keep anchors stable and ChangelogNote dates aligned with release dates; build web/PDF and check links after structural changes.

## Reusable author templates and coverage limits
Use docs/maintainers/authoring.md for reference, task and concept/overview templates. docs:coverage credits declared area/models even on draft pages; its documented percentage is not a completion score. Use doc_status to communicate readiness, and regenerate docs/maintainers/coverage.md through the command rather than editing it.
