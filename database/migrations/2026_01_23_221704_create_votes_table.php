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
        Schema::create('votes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('agenda_item_id')
                ->constrained('agenda_items')
                ->cascadeOnDelete();
            $table->boolean('is_main')->default(false);
            $table->string('title')->nullable(); // Optional name for what is being voted on
            $table->string('student_vote')->nullable(); // positive, negative, neutral
            $table->string('decision')->nullable(); // positive, negative, neutral
            $table->string('student_benefit')->nullable(); // positive, negative, neutral
            $table->text('note')->nullable(); // Optional note for this vote
            $table->integer('order')->default(0);
            $table->timestamps();

            // Index for efficient lookups
            $table->index(['agenda_item_id', 'is_main']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
