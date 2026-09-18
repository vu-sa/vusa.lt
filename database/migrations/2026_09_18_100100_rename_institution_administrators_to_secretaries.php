<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('institution_administrators', 'institution_secretaries');

        // Update any morph references in activity_log if present
        DB::table('activity_log')
            ->where('subject_type', 'institution_administrator')
            ->update(['subject_type' => 'institution_secretary']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('institution_secretaries', 'institution_administrators');

        DB::table('activity_log')
            ->where('subject_type', 'institution_secretary')
            ->update(['subject_type' => 'institution_administrator']);
    }
};
