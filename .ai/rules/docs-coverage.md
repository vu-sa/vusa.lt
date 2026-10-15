---
paths:
  - 'app/Console/Commands/DocsCoverageCommand.php'
  - 'app/Support/Docs/**'
---

# docs:coverage

## docs:coverage — feature/model documentation radar

`php artisan docs:coverage` reports how well the app's surface is documented, keyed on the **feature area** an admin recognises (`reservations`, `duties`) rather than raw route names. Areas come from the route table grouped by name prefix; each resolves to a model through `App\Support\MorphMap` via one `Str::snake(Str::singular())` normaliser that reconciles the route area (`reservations`) and the morph alias (`reservation`).

Two axes, deliberately one-directional (no false positives): **documented** (a human wrote a page for the area) and **tested** (a test names one of its routes). There is no inline-help signal: `docs/_parts` was retired, prose lives only in the reference pages and the admin UI links to them.

Pages declare `area`/`models`/`tests`/`last_reviewed` frontmatter; the rules for writing it live in docs.md.

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
