<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dates each succession link, so a pair can link again later (merged, then split back into the
 * same records). Existing links take the successor's first term, else the day after the
 * predecessor's last one, else the day the link was made.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duty_successions', function (Blueprint $table): void {
            $table->date('effective_on')->nullable()->after('successor_id');
        });

        DB::table('duty_successions')->orderBy('id')->each(function (object $link): void {
            DB::table('duty_successions')->where('id', $link->id)->update([
                'effective_on' => $this->backfillDate($link),
                'updated_at' => DB::raw('updated_at'),
            ]);
        });

        Schema::table('duty_successions', function (Blueprint $table): void {
            $table->date('effective_on')->nullable(false)->change();
            // Added before the old index goes: MySQL needs an index starting with predecessor_id for its foreign key.
            $table->unique(['predecessor_id', 'successor_id', 'effective_on'], 'duty_successions_link_unique');
            $table->dropUnique(['predecessor_id', 'successor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('duty_successions', function (Blueprint $table): void {
            $table->unique(['predecessor_id', 'successor_id']);
            $table->dropUnique('duty_successions_link_unique');
            $table->dropColumn('effective_on');
        });
    }

    private function backfillDate(object $link): string
    {
        $firstStart = DB::table('dutiables')->where('duty_id', $link->successor_id)->min('start_date');

        if ($firstStart !== null) {
            return Carbon::parse($firstStart)->toDateString();
        }

        $lastEnd = DB::table('dutiables')->where('duty_id', $link->predecessor_id)->max('end_date');

        if ($lastEnd !== null) {
            return Carbon::parse($lastEnd)->addDay()->toDateString();
        }

        return Carbon::parse($link->created_at ?? now())->toDateString();
    }
};
