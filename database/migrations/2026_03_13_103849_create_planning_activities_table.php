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
        Schema::create('planning_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('planning_process_id');
            $table->string('name', 255);
            $table->tinyInteger('month');
            $table->string('responsible_person', 255)->nullable();
            $table->string('level', 50);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('planning_process_id')->references('id')->on('planning_processes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_activities');
    }
};
