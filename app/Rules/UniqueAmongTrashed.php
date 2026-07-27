<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A `unique` check for a column that carries a database UNIQUE index on a
 * soft-deletable table.
 *
 * Laravel offers `Rule::unique(...)->withoutTrashed()`, which excludes soft-deleted
 * rows so validation *passes*. That is the right behaviour when uniqueness is enforced
 * only in the application — but where the database also has a UNIQUE index the trashed
 * row genuinely still occupies the key, so letting validation pass just converts a
 * confusing message into an "Integrity constraint violation" 500.
 *
 * This rule therefore keeps rejecting, and instead fixes the *message*: when the row
 * holding the value turns out to be soft-deleted it says so, and says what to do about
 * it, rather than claiming the value is "already taken" by a record the user cannot
 * find anywhere in the interface.
 *
 * @see SoftDeleteRules::existsLive() for the mirrored problem on `exists`
 */
class UniqueAmongTrashed implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    /** @var array<int, array{0: string, 1: mixed}> */
    protected array $wheres = [];

    protected int|string|null $ignoreId = null;

    public function __construct(
        protected string $table,
        protected ?string $column = null,
    ) {}

    public static function of(string $table, ?string $column = null): self
    {
        return new self($table, $column);
    }

    /**
     * Exclude the record being edited, so an update that leaves the value untouched
     * does not collide with itself.
     */
    public function ignore(int|string|null $id): self
    {
        $this->ignoreId = $id;

        return $this;
    }

    /**
     * Narrow the uniqueness scope, mirroring the columns of a composite index.
     */
    public function where(string $column, mixed $value): self
    {
        $this->wheres[] = [$column, $value];

        return $this;
    }

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $column = $this->column ?? $attribute;

        if ($this->passesUniqueCheck($value, $column)) {
            return;
        }

        // The value is taken. Whether the holder is live or trashed decides which of
        // the two very different messages the user needs.
        if ($this->conflictIsTrashed($column, $value)) {
            $fail('validation.unique_trashed')->translate([
                'attribute' => $this->validator->getDisplayableAttribute($attribute),
            ]);

            return;
        }

        $fail('validation.unique')->translate([
            'attribute' => $this->validator->getDisplayableAttribute($attribute),
        ]);
    }

    /**
     * Delegate to the framework's own `unique` rule rather than reimplementing its
     * ignore/where/connection handling.
     */
    protected function passesUniqueCheck(mixed $value, string $column): bool
    {
        $rule = Rule::unique($this->table, $column);

        if ($this->ignoreId !== null) {
            $rule->ignore($this->ignoreId);
        }

        foreach ($this->wheres as [$whereColumn, $whereValue]) {
            $rule->where($whereColumn, $whereValue);
        }

        // Validated under a fixed, dot-free key. Nested attributes arrive here as
        // `new_users.0.email`, and a nested validator would read those dots as array
        // access, find nothing, and pass the check vacuously.
        return ValidatorFactory::make(['value' => $value], ['value' => $rule])->passes();
    }

    protected function conflictIsTrashed(string $column, mixed $value): bool
    {
        $query = DB::table($this->table)
            ->where($column, $value)
            ->whereNotNull('deleted_at');

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        foreach ($this->wheres as [$whereColumn, $whereValue]) {
            $query->where($whereColumn, $whereValue);
        }

        return $query->exists();
    }
}
