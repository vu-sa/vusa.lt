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
            $table->dropColumn('outcome');
            $table->text('description')->nullable()->after('title');
            $table->string('student_vote')->nullable()->after('description');
            $table->string('decision')->nullable()->after('student_vote');
            $table->string('student_benefit')->nullable()->after('decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->string('outcome')->nullable()->after('title');
            $table->dropColumn('description');
            $table->dropColumn('student_vote');
            $table->dropColumn('decision');
            $table->dropColumn('student_benefit');
        });
    }
};
