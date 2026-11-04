<?php

use App\Models\Meeting;
use Illuminate\Database\Migrations\Migration;

/**
 * Stored titles predate meeting kinds and called every email meeting a posėdis. Titles only:
 * the deploy's `search:reindex` step picks them up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Meeting::query()
            ->withTrashed()
            ->with('institutions.types')
            ->chunkById(200, fn ($meetings) => $meetings->each->refreshTitle());
    }

    public function down(): void
    {
        // Generated values: the previous titles carry no information of their own.
    }
};
