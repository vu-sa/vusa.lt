<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links a duty to the one(s) that took over its work, e.g. a combined coordinator split in two. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_successions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('predecessor_id')->constrained('duties')->cascadeOnDelete();
            $table->foreignUlid('successor_id')->constrained('duties')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['predecessor_id', 'successor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_successions');
    }
};
