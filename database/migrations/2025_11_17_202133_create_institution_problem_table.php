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
        Schema::create('institution_problem', function (Blueprint $table) {
            $table->foreignUlid('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('problem_id')->constrained()->cascadeOnDelete();
            $table->primary(['institution_id', 'problem_id'], 'institution_problem_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_problem');
    }
};
