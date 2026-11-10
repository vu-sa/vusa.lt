<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_sections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('learning_steps', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->nullable()->unique();
            $table->boolean('is_system')->default(false);
            $table->string('kind');
            $table->foreignUlid('learning_section_id')->nullable()->constrained('learning_sections')->nullOnDelete();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->boolean('per_term')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('learning_step_audiences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('learning_step_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('source_id', 26)->nullable();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('importance');
            $table->string('stage')->nullable();
            $table->json('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->index(['type', 'source_id']);
        });
        Schema::create('learning_step_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('learning_step_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 2048)->nullable();
            $table->timestamps();
            $table->unique(['learning_step_id', 'locale']);
        });
        Schema::create('learning_step_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('learning_step_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['learning_step_id', 'user_id']);
        });
        Schema::create('glossary_terms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->json('term');
            $table->json('definition');
            $table->timestamps();
        });
        Schema::create('glossary_term_learning_step', function (Blueprint $table): void {
            $table->foreignUlid('glossary_term_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('learning_step_id')->constrained()->cascadeOnDelete();
            $table->primary(['glossary_term_id', 'learning_step_id']);
        });
        Schema::create('handover_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('duty_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('dutiable_id')->nullable()->constrained('dutiables')->nullOnDelete();
            $table->foreignUlid('cadence_id')->nullable()->constrained('cadences')->nullOnDelete();
            foreach (['situation', 'open_issues', 'people', 'resources', 'advice'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
            $table->unique(['duty_id', 'user_id', 'cadence_id']);
        });
    }

    public function down(): void
    {
        foreach (['handover_notes', 'glossary_term_learning_step', 'glossary_terms', 'learning_step_progress', 'learning_step_sources', 'learning_step_audiences', 'learning_steps', 'learning_sections'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
