<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Moves rows from a merged record onto the kept one; shared by MergeUsers and MergeDuties. */
final class MergeRepointer
{
    public static function plain(string $table, string $column, string $keptId, string $mergedId): int
    {
        return DB::table($table)->where($column, $mergedId)->update([$column => $keptId]);
    }

    /**
     * Drop the merged record's rows the kept one already has a twin of (same values in
     * $sharedColumns), then move the rest — a unique index would reject the update.
     *
     * @param  list<string>  $sharedColumns
     * @param  array<string, string>  $scope  Extra equality constraints, e.g. the morph type
     */
    public static function deduped(string $table, string $ownerColumn, array $sharedColumns, string $keptId, string $mergedId, array $scope = []): int
    {
        DB::table($table)->where($scope)->where($ownerColumn, $mergedId)
            ->whereExists(function (Builder $twin) use ($table, $ownerColumn, $sharedColumns, $scope, $keptId): void {
                $twin->select(DB::raw(1))
                    ->from("{$table} as twin")
                    ->where("twin.{$ownerColumn}", $keptId);

                foreach ($scope as $column => $value) {
                    $twin->where("twin.{$column}", $value);
                }

                foreach ($sharedColumns as $column) {
                    $twin->where(fn (Builder $match) => $match
                        ->whereColumn("twin.{$column}", "{$table}.{$column}")
                        ->orWhere(fn (Builder $null) => $null->whereNull("twin.{$column}")->whereNull("{$table}.{$column}")));
                }
            })
            ->delete();

        return DB::table($table)->where($scope)->where($ownerColumn, $mergedId)->update([$ownerColumn => $keptId]);
    }

    /** @param  list<string>|null  $sharedColumns  null when the table has no unique index to respect */
    public static function morph(string $table, string $morph, ?array $sharedColumns, string $alias, string $keptId, string $mergedId): int
    {
        if ($sharedColumns !== null) {
            return self::deduped($table, "{$morph}_id", $sharedColumns, $keptId, $mergedId, ["{$morph}_type" => $alias]);
        }

        return DB::table($table)
            ->where("{$morph}_type", $alias)
            ->where("{$morph}_id", $mergedId)
            ->update(["{$morph}_id" => $keptId]);
    }
}
