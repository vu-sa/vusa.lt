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
        Schema::create('planning_resources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->smallInteger('academic_year_start')->index();
            $table->string('title', 255);
            $table->string('type', 20); // 'file', 'url', 'text'
            $table->text('content')->nullable(); // URL string or plaintext; null for files
            $table->string('category', 50)->nullable(); // 'tip_template', 'mvp_template', or null for custom
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_resources');
    }
};
