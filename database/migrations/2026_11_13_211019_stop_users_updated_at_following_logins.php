<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `users.updated_at` was ON UPDATE current_timestamp(), so the login heartbeat rewrote it on every visit
 * and "Atnaujinta" in Nariai read as a last-login time. It now moves only through Eloquent, and each value
 * goes back to the user's last logged edit (activity is kept a year), or their creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('updated_at')->useCurrent()->change();
        });

        $lastEdits = DB::table('activity_log')->where('subject_type', 'user')->whereIn('event', ['created', 'updated'])
            ->groupBy('subject_id')->selectRaw('subject_id, MAX(created_at) as edited_at')
            ->pluck('edited_at', 'subject_id');

        DB::table('users')->select(['id', 'created_at', 'updated_at'])->chunkById(500, function ($users) use ($lastEdits): void {
            foreach ($users as $user) {
                $edited = max((string) $user->created_at, (string) ($lastEdits[$user->id] ?? $user->created_at));
                if ($edited < (string) $user->updated_at) {
                    DB::table('users')->where('id', $user->id)->update(['updated_at' => $edited]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->change();
        });
    }
};
