<?php

/**
 * MergeDuties deletes the merged duties permanently, so a duty-referencing column it does
 * not know about either loses its rows (cascade) or blocks the merge.
 */

use App\Actions\MergeDuties;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

/** @return list<string> "table.column" entries MergeDuties handles, except morphs. */
function mergeDutiesHandledColumns(): array
{
    return collect(MergeDuties::REPOINT)
        ->map(fn (array $pair) => implode('.', $pair))
        ->concat(collect(MergeDuties::REPOINT_DEDUPED)->keys()->map(fn (string $table) => "{$table}.duty_id"))
        ->all();
}

test('every column referencing duties is handled by MergeDuties', function (): void {
    $referencing = collect(Schema::getTableListing(schemaQualified: false))
        ->reject(fn (string $table) => in_array($table, MergeDuties::CUSTOM, true))
        ->flatMap(function (string $table): array {
            $foreignKeys = collect(Schema::getForeignKeys($table))
                ->filter(fn (array $foreignKey) => $foreignKey['foreign_table'] === 'duties')
                ->flatMap(fn (array $foreignKey) => $foreignKey['columns']);

            $named = in_array('duty_id', Schema::getColumnListing($table), true) ? ['duty_id'] : [];

            return $foreignKeys->concat($named)->unique()->map(fn (string $column) => "{$table}.{$column}")->all();
        })
        ->values();

    expect($referencing)->toContain('duty_responsibilities.duty_id')
        ->and($referencing->diff(mergeDutiesHandledColumns())->values()->all())->toBeEmpty();
});

test('MergeDuties names only columns that exist', function (): void {
    $morphColumns = collect(MergeDuties::REPOINT_MORPH)
        ->flatMap(fn (array $morph, string $table) => ["{$table}.{$morph[0]}_type", "{$table}.{$morph[0]}_id"]);

    $missing = collect(mergeDutiesHandledColumns())
        ->concat($morphColumns)
        ->reject(fn (string $entry) => Schema::hasColumn(...explode('.', $entry)))
        ->concat(collect(MergeDuties::CUSTOM)->reject(fn (string $table) => Schema::hasTable($table)))
        ->values()
        ->all();

    expect($missing)->toBeEmpty();
});
