# Working across sessions and models

> **Admin redesign.** Read when: you are starting a session, choosing a model, or handing work over.
> Index: [README.md](README.md) · Plan: [plan.md](plan.md)

## Working across sessions and models

This plan will be carried out by Claude Code, Codex and Gemini over many sessions. What keeps the
result consistent:

### 1. Make the plan visible to every tool

The plan used to be one untracked root file listed in `.git/info/exclude`, which meant:
- tools that search with ripgrep skipped it — an agent never found it unless given the path;
- cloud or fresh-clone runs (e.g. Codex cloud) did not have it at all;
- and any session that did open it read ~1,600 lines to do one page.

It now lives in `.ai/redesign/admin/`, one file per purpose, committed.

**Commit it to `dev`** — so every branch cut from `dev` inherits it — together with the pointer in
`AGENTS.md`, which Codex reads directly and Gemini reads through `GEMINI.md`. The folder is deleted in
Phase 10, once its durable rules live in `.ai/rules`.

> Admin redesign in progress: before changing admin UI (`resources/js/Pages/Admin/**`, admin
> components, `AdminLayout`, admin emails or notifications), read
> `.ai/redesign/admin/README.md` and `playbook.md`, then only the rules file your task needs.

### 2. The plan is not the rules

- **This folder** says *what* and *when*: decisions, phases, checklists, notes.
- **`.ai/rules/`** says *how*, durably. Once a pilot proves a rule, record it with `record-rule`
  and a glob. Boost loads those for Claude Code, Codex, OpenCode and Antigravity (`boost.json`);
  make sure the Gemini client also has the Boost MCP server (`search-docs`, `database-query`,
  `record-rule`).

### 3. One unit of work per session

Start every session with the same prompt:

```
Read .ai/redesign/admin/README.md and playbook.md, plus the rules file(s) for this task
(rules/pages.md, rules/visual.md, rules/navigation.md, messages.md, reps.md as applicable),
and the row for this PR in plan.md.
Task: <one PR row, e.g. "PR 5.2 — Posėdžiai collection">.
Do not revisit decisions marked ✅ (see decisions.md for the reasoning). If one blocks you, stop and
add it to "Open questions" in plan.md.
Follow AGENTS.md and the matching .ai/rules files.
Finish by: running the per-phase gate; screenshots at 390/820/1180/1440, light + dark;
ticking the PR row and its checklist item; writing Notes — what changed, what surprised you,
what was NOT done, next step.
```

### 4. Match the model to the work

- **Cross-cutting and hard to undo** — tokens and the colour system, the catalog, the shell, the
  collection/record/form layouts, the ActionWindow restyle: the strongest model available, plus
  human review.
- **Playbook-driven** — long-tail pages once the layouts exist: faster models are fine; the
  playbook and the lint fence carry the consistency.
- **Always a second opinion** — before merging, a *different* model reviews the diff against the
  playbook, the Rules and the traps. Different models miss different things.

### 5. Guardrails that don't depend on who writes the code

- **Lint fence per migrated path, as each page lands** (not at the end): raw hues, `zinc`/`gray`/
  `white`, `rounded-*` and `shadow-*` are banned in `MIGRATED_ADMIN_PATHS`.
- **Stories** for every pattern component, a11y `error`.
- **Feature tests** for catalog visibility per persona; component tests for behaviour contracts.
- **Screenshots at four widths** with each PR.
- **Mark transitional code @deprecated**: any legacy route, shim, wrapper, or composable preserved
  until a later phase must carry an explicit `@deprecated` tag detailing its replacement and the
  PR/phase that will delete it.
- **Small PRs to `dev` behind the opt-in flag** — one pilot page or one long-tail group each,
  listing which playbook steps were done.

### 6. Which model, and in what thinking mode, per PR

Tiers, not product names — pick whatever is strongest in each tier at the time. Named tools below are
this project's current set (Claude Code, Codex, Gemini); the tier is what matters.

| Tier | Mode | What it is for | Review |
|---|---|---|---|
| **A** | Frontier model, **extended/high thinking**, plan first | Decisions with long reach: token and colour systems, the catalog, the shell, the shared page layouts, the notification contract, the switch. A wrong call here is paid for in every later PR. | A **different** frontier model reviews the diff against the playbook + Rules + traps, **and** a human looks at it |
| **B** | Strong model, **medium thinking** | Work with a clear target but real judgement: individual pilot pages, per-type message passes, overviews, the bigger long-tail groups, renames that cross layers. | A different model reviews; human skims the screenshots |
| **C** | Fast model, **low thinking**, strict prompt | Mechanical work with a checklist and a proven layout to copy: small bug fixes, data/label migrations, doc updates, the repetitive long-tail pages. | Lint fence + tests + a human skim |

| PR | Tier | Why |
|---|---|---|
| 0.2 recently-visited fix · 0.3 inventory · 0.6 role label | C | bounded, verifiable |
| 0.4 session continuity | B | touches auth and session config |
| 0.5 sekretorius rename | B | wide but mechanical; a missed reference breaks tasks and reminders |
| 2.1 surface tokens · 2.2 colour system | **A** | every later PR inherits these values |
| 2.3 StatusBadge · 2.4 entity registry · 2.7 pickers · 2.8 primitive audit | B | pattern work with real trade-offs |
| 2.5 empty/skeleton/progress · 2.6 dates · 2.9 lint scaffolding | C | small and self-contained |
| 3.1 catalog | **A** | the contract every menu and test depends on |
| 3.2 old shell reads catalog · 3.3 merge actions | B | refactor with behaviour to preserve |
| 4.1 shell + picker · 4.3 palette | **A** | the shape of the whole product |
| 4.2 mobile bar · 4.4 account menu · 4.5 breadcrumbs/badges · 4.6 prefetch/keyboard · 4.7 403 pages | B | visible behaviour, contained scope |
| 4.8 tour reset · 4.9 density removal · 4.10 device counter | C | deletion and counting |
| 5.2 collection · 5.3 record + editor · 5.5 form + sheet | **A** | each extracts a layout that ~40 pages will reuse |
| 5.1 Pradžia · 5.4 ActionWindow · 5.6 Rezervacijos · 5.7 Žymos · 5.8 ViSAK overview · 5.9 Mano rolės · 5.10 search | B | first users of a fresh layout |
| 6.1 notification contract | **A** | one contract, three renderings |
| 6.2 mail layout · 6.3/6.4 per-type passes · 6.5 push + colours | B | judgement per message, bounded by the policy table |
| 7.1–7.5 overviews · 7.6–7.10 rep features | B | product decisions already made, execution matters |
| 8.1 the switch | **A** | irreversible for users; removals must be complete |
| 8.2 announcement, changelog, docs | C | writing, with the plan as source |
| 9.1–9.3 institutions, reservations, users/duties | B | relations and occupancy logic |
| 9.4–9.9 problems, forms, content, files, system, account | C | playbook + an existing layout to copy |
| 10.1 fence · 10.2 record-rule + docs | B | the rules that outlive the plan |
| 10.3 delete the plan, re-measure | C | bookkeeping |

Tool suggestions for this project: **Claude Code** for the Vue/Tailwind/Inertia surface and anything
in tier A; **Codex** for wide mechanical backend edits and as the second-opinion reviewer on tier A;
**Gemini** (fast tier) for tier C passes and doc/label work. Whoever writes a tier-A PR must not also
review it.

Two standing rules, whatever the tier:

- **One PR per session.** Do not spawn parallel agents on the same PR row; they diverge on the same
  files and the reviewer cannot tell which decision was deliberate.
- **Tier C prompts name the model page to copy** ("follow `Pages/Admin/Content/IndexTag.vue` as
  migrated in PR 5.7") — a fast model with a reference page is reliable; without one it invents.

### 7. Hand-off hygiene

- Notes are written for a reader with no memory of the session: paths, measured values, what was
  *not* done.
- **Measured, not eyeballed** (the public revamp's lesson): colours, sizes and spacing are checked in
  a real browser before being claimed.
- When a decision changes, edit it in *Settled decisions* with the date — never two versions in
  two places.
