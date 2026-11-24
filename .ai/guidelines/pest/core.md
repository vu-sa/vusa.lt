@scoped(['tests/**'])
# Pest

- This project uses Pest. Create tests with `vendor/bin/sail artisan make:test --pest {name}`; do not include the suite directory in `{name}`.
- Read the `testing-best-practices` skill before writing tests. Do not delete tests without approval.
- Run one file serially (`vendor/bin/sail artisan test --compact path/to/Test.php`); anything wider runs with `--parallel` and exactly one path or `--filter`. Commands, TIA and worktree exceptions: `tests/CLAUDE.md`.
@endscoped
