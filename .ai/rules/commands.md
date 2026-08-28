---
paths:
  - app/Services/Typesense/**, app/Console/Commands/GenerateTypesenseSearchKey.php'
  - app/Console/Commands/DocsCoverageCommand.php
  - app/Support/Docs/**
---

# Commands

## Regenerate the Typesense search-only key after renaming public collections
Typesense API keys bake in the collection list at creation. Renaming/adding a public collection (e.g. news → public_news) does NOT update the existing key — searches against the new name get 401 while old names keep working, so only some pages break. After any change to TypesenseCollectionConfig::PUBLIC_COLLECTIONS, regenerate with `sail artisan typesense:generate-search-key` (confirm prompt; pipe `y` when non-interactive) and do the same on production.

## docs:coverage — feature/model documentation radar

`php artisan docs:coverage` reports how well the app's surface is documented, keyed on the **feature area** an admin recognises (`reservations`, `duties`) rather than raw route names. Areas come from the route table grouped by name prefix; each resolves to a model through `App\Support\MorphMap` via one `Str::snake(Str::singular())` normaliser that reconciles the route area (`reservations`), the morph alias (`reservation`) and the `docs/_parts` help dir (`reservations`).

Two axes, deliberately one-directional (no false positives): **documented** (a human wrote a page for the area) and **tested** (a test names one of its routes). A third, separate signal is **inline help** — a `docs/_parts/<model>` fragment feeding the admin UI.

### Frontmatter a page declares

Written by hand by whoever writes the prose, in the same file and commit:

```yaml
---
title: Rezervacijų sistema
area: reservations                      # the feature area this page documents
models: [Reservation, Resource]         # also credits those models' areas
last_reviewed: 2026-08-26               # anchors the drift radar
tests:                                  # evidence: proven by these files
  - tests/Feature/Admin/Resources/ReservationControllerTest.php
---
```

- **Attribution is by declaration only.** A page owns an area through `area:` or a class in `models:`. The old transitive route join is gone — a reservation page whose tests incidentally hit an approval route does **not** thereby document approvals.
- **Claims name test *files*, never `it()` names** — test names churn; a deleted or moved file is exactly what you want flagged.
- **`last_reviewed` is a plain date.** YAML turns an unquoted `2026-08-26` into a Unix timestamp; the scanner normalises timestamp / quoted string / DateTime alike, so either form works.
- `docs/en/**` (translations), `_parts`, `.vitepress`, `public` and `maintainers` are skipped, matched on the top-level dir, never as a substring. Frontmatter is parsed with `symfony/yaml`, so inline arrays and nested lists are fine.
- **`coverage: ignore`** opts a single page out entirely — handbook/procedure prose (FAQ, changelog, "how VU SR works") that no test can prove and that should not clutter the "no evidence cited" list. Prefer it over adding a whole directory to the exclusion const.

Route/test coverage is **not** the headline — this is a docs tool. The route surface is load-bearing plumbing (it is how areas are discovered and how "tested" is measured, with no hand-maintained registry), but the tested-% scorecard was removed; "N routes tested" survives only as a per-area ranking hint in the backlog.

### The rot radar

`last_reviewed` vs the newest commit date across a page's cited tests. Newer tests than the last review ⇒ the page **may have drifted** — its prose probably describes the old world. This is the standing question (`docs:coverage` any time), distinct from `--changed`, which is branch-scoped.

### Options and severities

| Option | Severity | Notes |
| --- | --- | --- |
| default report | advisory, exits 0 | Areas documented/tested, the ranked writing backlog, drift, never-reviewed. The moment the baseline gates, someone disables it and the signal is lost. |
| `--strict` | **gate** | Exit non-zero only for a dangling claim: a page citing a test file that no longer exists. |
| `--changed=<ref>` | advisory | Doc pages citing tests the branch touched, and the exact tests added/removed. `ChangedTestAnalyzer` warns (not silently no-ops) on a shallow clone / missing base ref. |
| `--area=<slug>` | — | Restrict the report to areas whose slug starts with the prefix; prints each area's routes as a tested/untested drill-down. |
| `--summary` | — | Append the Markdown report to `$GITHUB_STEP_SUMMARY` (per-step file). |
| `--dashboard` | — | Write the standing dashboard (`--dashboard-path`, default `docs/maintainers/coverage.md`). Deterministic: a no-change run rewrites it identically. |
| `--docs-path=<dir>` | — | Scan a different docs dir — used by the feature tests so they never touch the real `docs/`. |

### CI

`docs:coverage --strict --summary` runs on every push as the gate + job summary. On PRs, a second step rebuilds the report and the `--changed` list into a temp file and posts them as one **sticky PR comment** (`gh pr comment --edit-last --create-if-none`) — advisory, `continue-on-error`, needs `pull-requests: write`. Reviewing a page is a judgement call, and a gate on a judgement call gets disabled.

### Extraction

Route references are read from test source by AST (`TestSurfaceScanner`, `nikic/php-parser`), never regex: 26 test names carry escaped apostrophes that truncate a regex, and `pest --list-tests` mangles names and misattributes files. `route($variable)` is skipped — understating coverage is the safe direction. Admin API routes (`api.v1.admin.*`) fold into their feature area; public `api.v1.*` stays out.

### Traps

- Symfony Finder skips dotfiles: a doc named `.foo.md` is never scanned.
- `docs/maintainers/coverage.md` is generated and `srcExclude`d from the published site — read it in-repo, don't hand-edit it.
