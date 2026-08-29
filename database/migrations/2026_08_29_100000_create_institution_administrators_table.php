<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_administrators', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('institution_id', 26);
            // The term this nomination covers: the institution's own override when it has
            // any, otherwise the global row it inherits. Global rows are shared by every
            // body, which is why institution_id is not redundant here.
            $table->char('cadence_id', 26);
            $table->char('user_id', 26);
            $table->timestamps();

            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
            $table->foreign('cadence_id')->references('id')->on('cadences')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Named explicitly: the generated name would exceed MySQL's 64-char limit.
            $table->unique(['institution_id', 'cadence_id', 'user_id'], 'institution_administrators_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_administrators');
    }
};
