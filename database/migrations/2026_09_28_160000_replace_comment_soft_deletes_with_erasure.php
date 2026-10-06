<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A deleted comment used to be hidden with its words kept. Now it is erased: text, mentions,
 * reactions, votes and author are gone, and only a "Komentaras ištrintas" placeholder keeps its
 * place so replies stay in their thread. `user_id` becomes nullable because the author is erased too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->timestamp('erased_at')->nullable()->after('edited_at');
            $table->char('user_id', 26)->nullable()->change();
        });

        $trashed = DB::table('comments')->whereNotNull('deleted_at')->pluck('id');

        if ($trashed->isNotEmpty()) {
            DB::table('comment_reactions')->whereIn('comment_id', $trashed)->delete();
            DB::table('comment_poll_votes')->whereIn('comment_id', $trashed)->delete();
            DB::table('comments')->whereIn('id', $trashed)->update([
                'body' => '',
                'metadata' => null,
                'mentioned_user_ids' => null,
                'user_id' => null,
                'erased_at' => DB::raw('deleted_at'),
            ]);
        }

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }

    /**
     * Erased words and authors cannot come back; this only restores the columns.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->softDeletes();
        });

        DB::table('comments')->whereNotNull('erased_at')->update(['deleted_at' => DB::raw('erased_at')]);

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn('erased_at');
        });
    }
};
