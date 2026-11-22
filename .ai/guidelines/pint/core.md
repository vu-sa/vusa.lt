# Laravel Pint Code Formatter

- After changing PHP files, run `vendor/bin/sail bin pint --dirty --format agent` in the main checkout. In a git worktree `--dirty` cannot see git, so pass the changed paths instead: `vendor/bin/sail bin pint --format agent <paths>`.
- Do not run `pint --test`; run `pint --format agent` to fix formatting.
