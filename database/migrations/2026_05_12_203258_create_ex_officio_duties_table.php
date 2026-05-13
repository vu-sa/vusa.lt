<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ex_officio_duties', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('source_duty_id')->constrained('duties')->cascadeOnDelete();
            $table->foreignUlid('target_duty_id')->constrained('duties')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['source_duty_id', 'target_duty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ex_officio_duties');
    }
};
