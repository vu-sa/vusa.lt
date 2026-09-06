<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('content_parts')->where('type', 'spacer')->delete();
    }

    public function down(): void
    {
        // Removed spacers had no content, so their original position and size cannot be restored.
    }
};
