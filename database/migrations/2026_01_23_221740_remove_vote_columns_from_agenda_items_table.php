<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes old vote columns from agenda_items after data has been migrated.
     */
    public function up(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropColumn(['student_vote', 'decision', 'student_benefit']);
        });
    }

    /**
     * Reverse the migrations.
     * Re-adds vote columns to agenda_items for rollback.
     */
    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->string('student_vote')->nullable();
            $table->string('decision')->nullable();
            $table->string('student_benefit')->nullable();
        });
    }
};
