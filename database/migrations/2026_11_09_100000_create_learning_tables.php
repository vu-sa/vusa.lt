<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_sections', function (Blueprint $table): void {
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
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('action_label')->nullable();
            $table->json('action_url')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('knowledge_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key')->nullable()->unique();
            $table->foreignUlid('knowledge_section_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->json('title');
            $table->json('abbreviation')->nullable();
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('learning_audiences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulidMorphs('audienceable');
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
        Schema::create('knowledge_item_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('knowledge_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 2048)->nullable();
            $table->timestamps();
            $table->unique(['knowledge_item_id', 'locale']);
        });
        Schema::create('learning_progress', function (Blueprint $table): void {
            $table->id();
            $table->ulidMorphs('progressable');
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['progressable_type', 'progressable_id', 'user_id'], 'learning_progress_user_unique');
        });
        Schema::create('knowledge_links', function (Blueprint $table): void {
            $table->foreignUlid('from_item_id')->constrained('knowledge_items')->cascadeOnDelete();
            $table->foreignUlid('to_item_id')->constrained('knowledge_items')->cascadeOnDelete();
            $table->primary(['from_item_id', 'to_item_id']);
        });
        Schema::create('knowledge_item_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('knowledge_item_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type');
            $table->string('subject_id', 26);
            $table->unique(['knowledge_item_id', 'subject_type', 'subject_id'], 'knowledge_item_subjects_unique');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['knowledge_item_subjects', 'knowledge_links', 'learning_progress', 'knowledge_item_sources', 'learning_audiences', 'knowledge_items', 'learning_steps', 'knowledge_sections'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
