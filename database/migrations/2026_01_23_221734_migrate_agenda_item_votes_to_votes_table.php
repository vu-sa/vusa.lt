<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migrates existing vote data from agenda_items to the new votes table.
     *
     * Logic:
     * - Both neutral student_vote AND neutral decision → informational (no vote record)
     * - Has non-neutral student_vote OR non-neutral decision → voting (create vote record)
     * - No vote data set → type stays NULL (no vote record)
     */
    public function up(): void
    {
        // Get all agenda items that have any vote data
        $agendaItems = DB::table('agenda_items')
            ->where(function ($query) {
                $query->whereNotNull('student_vote')
                    ->orWhereNotNull('decision')
                    ->orWhereNotNull('student_benefit');
            })
            ->get();

        $now = now();

        foreach ($agendaItems as $item) {
            // Check if this is an informational item (both neutral)
            $isInformational = $item->student_vote === 'neutral' && $item->decision === 'neutral';

            if ($isInformational) {
                // Mark as informational, don't create vote record
                DB::table('agenda_items')
                    ->where('id', $item->id)
                    ->update([
                        'type' => 'informational',
                    ]);

                continue;
            }

            // Check if there's actual voting data (non-neutral values)
            $hasVotingData = ($item->student_vote && $item->student_vote !== 'neutral')
                || ($item->decision && $item->decision !== 'neutral')
                || $item->student_benefit;

            if ($hasVotingData) {
                // Create vote record for voting items
                DB::table('votes')->insert([
                    'id' => Str::ulid(),
                    'agenda_item_id' => $item->id,
                    'is_main' => true,
                    'title' => null,
                    'student_vote' => $item->student_vote,
                    'decision' => $item->decision,
                    'student_benefit' => $item->student_benefit,
                    'note' => null,
                    'order' => 0,
                    'created_at' => $item->created_at ?? $now,
                    'updated_at' => $item->updated_at ?? $now,
                ]);

                // Set the agenda item type to voting
                DB::table('agenda_items')
                    ->where('id', $item->id)
                    ->update([
                        'type' => 'voting',
                    ]);
            }
            // Items without meaningful vote data keep type as NULL
        }
    }

    /**
     * Reverse the migrations.
     * Moves vote data back to agenda_items from votes table.
     */
    public function down(): void
    {
        // Get main votes and update their agenda items
        $votes = DB::table('votes')
            ->where('is_main', true)
            ->get();

        foreach ($votes as $vote) {
            DB::table('agenda_items')
                ->where('id', $vote->agenda_item_id)
                ->update([
                    'student_vote' => $vote->student_vote,
                    'decision' => $vote->decision,
                    'student_benefit' => $vote->student_benefit,
                ]);
        }

        // Delete all votes (they'll be recreated if migration runs again)
        DB::table('votes')->truncate();
    }
};
