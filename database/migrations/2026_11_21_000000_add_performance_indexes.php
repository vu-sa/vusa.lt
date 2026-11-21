<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<array{table: string, columns: list<string>, name: string}> */
    private const array INDEXES = [
        [
            'table' => 'dutiables',
            'columns' => ['dutiable_type', 'dutiable_id', 'end_date'],
            'name' => 'dutiables_type_id_end_idx',
        ],
        [
            'table' => 'dutiables',
            'columns' => ['duty_id', 'dutiable_type', 'end_date'],
            'name' => 'dutiables_duty_type_end_idx',
        ],
        [
            'table' => 'activity_log',
            'columns' => ['causer_type', 'causer_id', 'created_at'],
            'name' => 'activity_log_causer_created_idx',
        ],
        [
            'table' => 'activity_log',
            'columns' => ['root_subject_type', 'root_subject_id', 'created_at'],
            'name' => 'activity_log_root_created_idx',
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as ['table' => $tableName, 'columns' => $columns, 'name' => $indexName]) {
            if (Schema::hasIndex($tableName, $columns) || Schema::hasIndex($tableName, $indexName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
                $table->index($columns, $indexName);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::INDEXES) as ['table' => $tableName, 'name' => $indexName]) {
            if (! Schema::hasIndex($tableName, $indexName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
            });
        }
    }
};
