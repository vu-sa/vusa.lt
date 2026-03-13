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
        Schema::create('planning_stage_deadlines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->smallInteger('academic_year_start');
            $table->tinyInteger('stage');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->timestamps();

            $table->unique(['academic_year_start', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_stage_deadlines');
    }
};
