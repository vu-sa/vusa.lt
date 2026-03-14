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
        Schema::create('planning_process_editors', function (Blueprint $table) {
            $table->id();
            $table->ulid('planning_process_id');
            $table->ulid('user_id');
            $table->timestamps();

            $table->foreign('planning_process_id')->references('id')->on('planning_processes')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['planning_process_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_process_editors');
    }
};
