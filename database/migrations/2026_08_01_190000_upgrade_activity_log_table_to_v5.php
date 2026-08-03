<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Upgrade the activity_log table from the spatie/laravel-activitylog v4 schema
     * to the v5 schema: add attribute_changes (tracked model changes), drop the
     * batch_uuid column (batch system removed), and migrate existing 'attributes'
     * and 'old' data out of properties into attribute_changes.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->json('attribute_changes')->nullable()->after('causer_id');
        });

        DB::table('activity_log')->whereNotNull('properties')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $properties = json_decode((string) $row->properties, true) ?? [];
                $changes = array_intersect_key($properties, array_flip(['attributes', 'old']));
                $remaining = array_diff_key($properties, array_flip(['attributes', 'old']));

                DB::table('activity_log')->where('id', $row->id)->update([
                    'attribute_changes' => empty($changes) ? null : json_encode($changes),
                    'properties' => empty($remaining) ? null : json_encode($remaining),
                ]);
            }
        });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn('batch_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->char('batch_uuid', 36)->nullable();
        });

        DB::table('activity_log')->whereNotNull('attribute_changes')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $changes = json_decode((string) $row->attribute_changes, true) ?? [];
                $properties = json_decode((string) $row->properties, true) ?? [];
                $merged = array_merge($properties, $changes);

                DB::table('activity_log')->where('id', $row->id)->update([
                    'properties' => empty($merged) ? null : json_encode($merged),
                ]);
            }
        });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn('attribute_changes');
        });
    }
};
