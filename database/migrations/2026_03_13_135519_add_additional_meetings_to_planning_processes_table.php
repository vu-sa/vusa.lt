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
        Schema::table('planning_processes', function (Blueprint $table) {
            $table->json('additional_meetings')->nullable()->after('meeting_2_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planning_processes', function (Blueprint $table) {
            $table->dropColumn('additional_meetings');
        });
    }
};
