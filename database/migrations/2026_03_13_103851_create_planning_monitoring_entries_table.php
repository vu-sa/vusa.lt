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
        Schema::create('planning_monitoring_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('planning_process_id');
            $table->tinyInteger('quarter');
            $table->text('status_text');
            $table->ulid('submitted_by');
            $table->timestamps();

            $table->foreign('planning_process_id')->references('id')->on('planning_processes')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['planning_process_id', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_monitoring_entries');
    }
};
