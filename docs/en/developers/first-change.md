---
title: Your first change
---

# Your first change

This page walks through one change from branch to pull request. It assumes the project already runs
locally, as described in the [README](https://github.com/vu-sa/vusa.lt/blob/dev/README.md#set-up-locally)
(`./dev/setup.sh`, the hosts file, the certificate, login `test@test.com` / `password`).

## 1. Pick something to change

Start small: a bug you can reproduce, a copy fix or a missing test. Open work is in
[GitHub issues](https://github.com/vu-sa/vusa.lt/issues). Agree a new feature in an issue or with the
maintainers first, so the work isn't wasted.

## 2. Branch from `dev`

All work goes into `dev`, which deploys to staging. `main` is production and only changes through
releases from `dev`.

```bash
git fetch origin
git switch -c fix/short-name origin/dev
```

Without write access to the repository, fork it first and branch in your fork. When you need several
branches at once, `./dev/worktree.sh add <name>` creates a separate git worktree under `.worktrees/` and
prepares it (`.env`, dependencies, `public/build`).

## 3. Read the rules for the files you touch

The shared rules are in [`AGENTS.md`](https://github.com/vu-sa/vusa.lt/blob/dev/AGENTS.md). Rules for
specific places live in `.ai/rules/`: open
[`.ai/rules/index.md`](https://github.com/vu-sa/vusa.lt/blob/dev/.ai/rules/index.md), find the rows whose
globs cover your files, and read those rules. Some folders also have a `CLAUDE.md` with a longer guide
(for example `tests/CLAUDE.md` and `resources/js/Components/CLAUDE.md`). AI agents read the same rules, so
the code reads the same whoever wrote it.

## 4. Check the change

```bash
./dev/check-changed.sh
```

It checks only the files your branch changed since `origin/dev`:

- PHP files are formatted with Pint and checked with PHPMD;
- JS and Vue files go through ESLint and the TypeScript ratchet, and their related Vitest tests run;
- Pest runs through Test Impact Analysis, so only the tests your change affects run. TIA doesn't work in a worktree, so there only the changed test files run.

CI runs the whole suite once you open the pull request.

## 5. When the change is done

- Behaviour, business rules, security boundaries and likely regressions have a test. Copy, styling and
  removing dead code don't need one.
- User-facing text exists in both Lithuanian and English.
- A user-facing change has entries in `docs/changelog/v3.md` and `docs/en/changelog/v3.md`, and the
  guide page for that section is updated.
- UI changes are checked at phone width and in both themes.
- `./dev/check-changed.sh` passes.

## 6. Open a pull request into `dev`

Push the branch (`git push -u origin fix/short-name`) and open a pull request into `dev`. The template
lists the same points. Say briefly what changed and why, and link the issue it closes. Review and CI
will show what is still missing.
