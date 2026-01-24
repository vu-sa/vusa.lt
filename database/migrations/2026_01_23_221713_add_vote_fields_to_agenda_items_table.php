<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            // Type of agenda item: voting, informational, deferred
            $table->string('type')->nullable()->after('brought_by_students');
            // Student position statement for the entire agenda item
            $table->text('student_position')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropColumn(['type', 'student_position']);
        });
    }
};
