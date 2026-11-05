<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')->where('sync_status', 'success')->update([
            'sync_attempts' => 0,
            'updated_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        // Historical attempt counts cannot be recovered.
    }
};
