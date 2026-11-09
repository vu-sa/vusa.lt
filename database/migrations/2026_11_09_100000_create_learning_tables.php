<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_steps', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->nullable()->unique();
            $table->boolean('is_system')->default(false);
            $table->string('kind');
            $table->string('layer');
            $table->foreignId('institution_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('duty_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('stage')->nullable();
            $table->string('importance');
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->boolean('per_term')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['layer', 'is_active']);
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
        foreach (['handover_notes', 'glossary_term_learning_step', 'glossary_terms', 'learning_step_progress', 'learning_step_sources', 'learning_steps'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
