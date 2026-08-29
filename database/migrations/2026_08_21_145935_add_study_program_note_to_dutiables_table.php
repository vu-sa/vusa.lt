<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which group inside the programme a curator was assigned to. A programme alone does
     * not identify the seat in the large ones, where several curators split the intake.
     */
    public function up(): void
    {
        Schema::table('dutiables', function (Blueprint $table) {
            $table->json('study_program_note')->nullable()->after('study_program_id');
        });
    }

    public function down(): void
    {
        Schema::table('dutiables', function (Blueprint $table) {
            $table->dropColumn('study_program_note');
        });
    }
};
