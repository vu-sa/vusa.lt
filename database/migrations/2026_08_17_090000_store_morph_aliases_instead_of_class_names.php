<?php

use App\Support\MorphMap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrite every polymorphic column from `App\Models\X` to the morph alias `x` (see
 * App\Support\MorphMap). Values that are not class names are untouched, so a re-run is a no-op.
 */
return new class extends Migration
{
    /**
     * table => columns. `documents.content_type`, `fileable_files.{file,mime}_type`,
     * `media.mime_type` and `tasks.action_type` share the suffix but are not polymorphic.
     *
     * @var array<string, array<int, string>>
     */
    private const COLUMNS = [
        'activity_log' => ['subject_type', 'root_subject_type', 'causer_type'],
        'approvals' => ['approvable_type'],
        'approval_flows' => ['flowable_type'],
        'comments' => ['commentable_type'],
        'dutiables' => ['dutiable_type'],
        'fileable_files' => ['fileable_type'],
        'media' => ['model_type'],
        'model_has_roles' => ['model_type'],
        'model_has_permissions' => ['model_type'],
        'notifications' => ['notifiable_type'],
        'push_subscriptions' => ['subscribable_type'],
        'relationshipables' => ['relationshipable_type'],
        'sharepoint_fileables' => ['fileable_type'],
        'tasks' => ['taskable_type'],
        'typeables' => ['typeable_type'],
        'types' => ['model_type'],
        'workspace_links' => ['linkable_type'],
    ];

    /** Removed by 2025_12_10_045517_remove_tasks_with_doing_taskable_type, rows left behind. */
    private const DELETED_CLASSES = ['App\Models\Doing'];

    /** Survey arrives from a separate branch — alias its rows now, leave them in place. */
    private const ORPHAN_ALIASES = ['App\Models\Survey' => 'survey'];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereIn($column, self::DELETED_CLASSES)
                    ->delete();

                foreach (MorphMap::MAP as $alias => $class) {
                    DB::table($table)->where($column, $class)->update([$column => $alias]);
                }

                foreach (self::ORPHAN_ALIASES as $class => $alias) {
                    DB::table($table)->where($column, $class)->update([$column => $alias]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                foreach (MorphMap::MAP as $alias => $class) {
                    DB::table($table)->where($column, $alias)->update([$column => $class]);
                }

                foreach (self::ORPHAN_ALIASES as $class => $alias) {
                    DB::table($table)->where($column, $alias)->update([$column => $class]);
                }
            }
        }

        // Deleted-class rows are not restored — the class is gone in either direction.
    }
};
