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
        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->date('start_date');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->onDelete('cascade');
            $table->json('title');
            $table->json('description')->nullable();
            $table->integer('order');
            $table->dateTime('start_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_parts', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->integer('duration');
            $table->time('start_time')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_sections', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->integer('duration');
            $table->time('start_time')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_section_id')->constrained()->onDelete('cascade');
            $table->json('title');
            $table->json('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programmables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->onDelete('cascade');
            $table->ulid('programmable_id');
            $table->string('programmable_type');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_day_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_day_id')->constrained()->onDelete('cascade');
            $table->morphs('elementable');
            $table->integer('order');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_block_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_block_id')->constrained()->onDelete('cascade');
            $table->foreignId('programme_part_id')->constrained()->onDelete('cascade');
            $table->integer('order');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programme_block_part');
        Schema::dropIfExists('programme_day_elements');
        Schema::dropIfExists('programme_parts');
        Schema::dropIfExists('programme_blocks');
        Schema::dropIfExists('programme_sections');
        Schema::dropIfExists('programme_days');
        Schema::dropIfExists('programmables');
        Schema::dropIfExists('programmes');
    }
};
