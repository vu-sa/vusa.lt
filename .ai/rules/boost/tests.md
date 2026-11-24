---
paths:
  - 'tests/**'
---

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

# Pest

- This project uses Pest. Create tests with `vendor/bin/sail artisan make:test --pest {name}`; do not include the suite directory in `{name}`.
- Read the `testing-best-practices` skill before writing tests. Do not delete tests without approval.
- Run one file serially (`vendor/bin/sail artisan test --compact path/to/Test.php`); anything wider runs with `--parallel` and exactly one path or `--filter`. Commands, TIA and worktree exceptions: `tests/CLAUDE.md`.
