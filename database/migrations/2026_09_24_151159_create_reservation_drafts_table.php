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
        Schema::create('reservation_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('reservation_draft_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('resource_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['reservation_draft_id', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_draft_items');
        Schema::dropIfExists('reservation_drafts');
    }
};
