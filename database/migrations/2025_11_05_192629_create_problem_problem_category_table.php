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
        Schema::create('problem_problem_category', function (Blueprint $table) {
            $table->foreignUlid('problem_id')->constrained()->cascadeOnDelete();
            $table->foreignId('problem_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['problem_id', 'problem_category_id'], 'problem_category_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_problem_category');
    }
};
