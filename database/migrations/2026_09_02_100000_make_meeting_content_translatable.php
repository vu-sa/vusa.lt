<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts the authored meeting text to Spatie-translatable JSON columns.
 *
 * Only `lt` is seeded. The tags/categories precedent duplicated the source value into `en`,
 * but that would mark every existing row as translated — which both defeats the models'
 * `lt` fallback and hides the admin's "this item already has English" affordance.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'agenda_items' => ['title', 'description', 'student_position'],
        'votes' => ['title', 'note'],
        'meetings' => ['description'],
    ];

    /**
     * Restored as NOT NULL on rollback, so an absent translation becomes '' rather than null.
     *
     * @var array<string, list<string>>
     */
    private const REQUIRED_COLUMNS = [
        'agenda_items' => ['title'],
    ];

    /**
     * Columns that were not `text` before the conversion, restored to their original type.
     *
     * @var array<string, array<string, int>>
     */
    private const VARCHAR_LENGTHS = [
        'votes' => ['title' => 200],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            // Four separate Schema::table() calls per table: MariaDB does not reliably
            // drop and rename columns within one statement batch.
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->json($column.'_translations')->nullable();
                }
            });

            DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunk(500, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        $updates[$column.'_translations'] = ($value === null || $value === '')
                            ? null
                            : json_encode(['lt' => $value], JSON_UNESCAPED_UNICODE);
                    }

                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            });

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->dropColumn($columns);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->renameColumn($column.'_translations', $column);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $required = self::REQUIRED_COLUMNS[$table] ?? [];

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->text($column.'_string')->nullable();
                }
            });

            DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunk(500, function ($rows) use ($table, $columns, $required): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $decoded = $row->{$column} === null ? null : json_decode($row->{$column}, true);
                        $value = is_array($decoded) ? ($decoded['lt'] ?? $decoded['en'] ?? null) : null;

                        $updates[$column.'_string'] = $value ?? (in_array($column, $required, true) ? '' : null);
                    }

                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            });

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->dropColumn($columns);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->renameColumn($column.'_string', $column);
                }
            });

            $lengths = self::VARCHAR_LENGTHS[$table] ?? [];

            if ($required !== [] || $lengths !== []) {
                Schema::table($table, function (Blueprint $blueprint) use ($required, $lengths): void {
                    foreach ($required as $column) {
                        $blueprint->text($column)->nullable(false)->change();
                    }

                    foreach ($lengths as $column => $length) {
                        $blueprint->string($column, $length)->nullable()->change();
                    }
                });
            }
        }
    }
};
