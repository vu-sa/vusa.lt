<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a denormalised "root subject" to every activity row so a whole
     * meeting/institution tree can be queried in one indexed lookup instead of
     * requiring the UI to know which child models roll up to which parent.
     *
     * A separate migration from 2026_08_01_190000_upgrade_activity_log_table_to_v5
     * on purpose: that one is a reversible v4->v5 schema move with its own
     * concern, and may already be applied on some machines -- editing an
     * applied migration means the new columns silently never appear there.
     *
     * Kept in sync with App\Support\ActivityRoots::PARENTS, which the runtime
     * resolver (App\Services\ActivityRootResolver) uses for newly logged rows.
     *
     * The backfill uses correlated subqueries (portable SQL, works on both the
     * MySQL production database and the SQLite in-memory test database) rather
     * than a multi-table UPDATE...JOIN, which SQLite does not support. Class
     * name strings are passed as bound parameters, never embedded as SQL
     * string literals -- MySQL treats backslash as an escape character inside
     * single-quoted literals, which silently corrupts a namespaced class name
     * like 'App\Models\Institution' into 'AppModelsInstitution'.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->string('root_subject_type')->nullable()->after('subject_id');
            $table->string('root_subject_id', 26)->nullable()->after('root_subject_type');

            $table->index(['root_subject_type', 'root_subject_id'], 'root_subject');
        });

        // Step 1: self-root everything. Re-pointed below where a known child
        // subject type has a parent.
        DB::table('activity_log')
            ->whereNull('root_subject_type')
            ->whereNotNull('subject_type')
            ->update([
                'root_subject_type' => DB::raw('subject_type'),
                'root_subject_id' => DB::raw('subject_id'),
            ]);

        // Step 2: Duty -> Institution.
        DB::statement(<<<'SQL'
            UPDATE activity_log
            SET root_subject_type = ?,
                root_subject_id = (SELECT d.institution_id FROM duties d WHERE d.id = activity_log.subject_id)
            WHERE subject_type = ?
              AND EXISTS (SELECT 1 FROM duties d WHERE d.id = activity_log.subject_id AND d.institution_id IS NOT NULL)
        SQL, ['App\Models\Institution', 'App\Models\Duty']);

        // Step 3: AgendaItem -> Meeting.
        DB::statement(<<<'SQL'
            UPDATE activity_log
            SET root_subject_type = ?,
                root_subject_id = (SELECT ai.meeting_id FROM agenda_items ai WHERE ai.id = activity_log.subject_id)
            WHERE subject_type = ?
              AND EXISTS (SELECT 1 FROM agenda_items ai WHERE ai.id = activity_log.subject_id AND ai.meeting_id IS NOT NULL)
        SQL, ['App\Models\Meeting', 'App\Models\Pivots\AgendaItem']);

        // Step 4: Vote -> AgendaItem -> Meeting.
        DB::statement(<<<'SQL'
            UPDATE activity_log
            SET root_subject_type = ?,
                root_subject_id = (
                    SELECT ai.meeting_id FROM votes v
                    JOIN agenda_items ai ON ai.id = v.agenda_item_id
                    WHERE v.id = activity_log.subject_id
                )
            WHERE subject_type = ?
              AND EXISTS (
                  SELECT 1 FROM votes v
                  JOIN agenda_items ai ON ai.id = v.agenda_item_id
                  WHERE v.id = activity_log.subject_id AND ai.meeting_id IS NOT NULL
              )
        SQL, ['App\Models\Meeting', 'App\Models\Vote']);

        // Step 5: AgendaItemNote -> AgendaItem -> Meeting.
        DB::statement(<<<'SQL'
            UPDATE activity_log
            SET root_subject_type = ?,
                root_subject_id = (
                    SELECT ai.meeting_id FROM agenda_item_notes n
                    JOIN agenda_items ai ON ai.id = n.agenda_item_id
                    WHERE n.id = activity_log.subject_id
                )
            WHERE subject_type = ?
              AND EXISTS (
                  SELECT 1 FROM agenda_item_notes n
                  JOIN agenda_items ai ON ai.id = n.agenda_item_id
                  WHERE n.id = activity_log.subject_id AND ai.meeting_id IS NOT NULL
              )
        SQL, ['App\Models\Meeting', 'App\Models\AgendaItemNote']);
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex('root_subject');
            $table->dropColumn(['root_subject_type', 'root_subject_id']);
        });
    }
};
