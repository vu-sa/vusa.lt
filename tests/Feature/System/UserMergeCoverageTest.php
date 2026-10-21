<?php

/**
 * MergeUsers deletes the merged accounts permanently, so a user-referencing column
 * it does not know about either loses its rows (cascade) or blocks the merge.
 * Adding such a column means deciding, in MergeUsers, whether it moves or drops.
 */

use App\Actions\MergeUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

/** @return list<string> "table.column" entries MergeUsers handles. */
function mergeUsersHandledColumns(): array
{
    return collect(MergeUsers::REPOINT)
        ->map(fn (array $pair) => implode('.', $pair))
        ->concat(collect(MergeUsers::REPOINT_DEDUPED)->keys()->map(fn (string $table) => "{$table}.user_id"))
        ->concat(collect(MergeUsers::DROP)->map(fn (string $table) => "{$table}.user_id"))
        ->all();
}

test('every column referencing users is handled by MergeUsers', function (): void {
    $referencing = collect(Schema::getTableListing(schemaQualified: false))
        ->flatMap(function (string $table): array {
            $foreignKeys = collect(Schema::getForeignKeys($table))
                ->filter(fn (array $foreignKey) => $foreignKey['foreign_table'] === 'users')
                ->flatMap(fn (array $foreignKey) => $foreignKey['columns']);

            // Pivots without a declared foreign key (task_user, sessions) still name the column user_id.
            $named = in_array('user_id', Schema::getColumnListing($table), true) ? ['user_id'] : [];

            return $foreignKeys->concat($named)->unique()->map(fn (string $column) => "{$table}.{$column}")->all();
        })
        // Direct roles and permissions refuse the merge instead (MergeUsersRequest).
        ->reject(fn (string $column) => str_starts_with($column, 'model_has_'))
        ->values();

    expect($referencing)->toContain('comments.user_id')
        ->and($referencing->diff(mergeUsersHandledColumns())->values()->all())->toBeEmpty();
});

test('MergeUsers names only columns that exist', function (): void {
    $morphColumns = collect(MergeUsers::REPOINT_MORPH)
        ->flatMap(fn (array $morph, string $table) => ["{$table}.{$morph[0]}_type", "{$table}.{$morph[0]}_id"]);

    $missing = collect(mergeUsersHandledColumns())
        ->concat($morphColumns)
        ->reject(fn (string $entry) => Schema::hasColumn(...explode('.', $entry)))
        ->values()
        ->all();

    expect($missing)->toBeEmpty();
});
