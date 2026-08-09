<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The questions of one survey.
 *
 * Content is copied here, never referenced: `survey_question_template_id` records where a
 * question came from, but a null value is equally valid and simply means the author wrote
 * it themselves. That is what lets one survey mix bank questions with custom ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('survey_id')->constrained()->cascadeOnDelete();

            // Provenance only. Nulled rather than cascaded when the template is removed,
            // so deleting a bank entry never destroys questions on an existing survey.
            $table->foreignUlid('survey_question_template_id')->nullable()
                ->constrained('survey_question_templates')->nullOnDelete();

            $table->json('group_name');
            $table->string('title', 20);
            $table->string('type', 5);
            $table->json('question');
            $table->json('help')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order')->default(0);

            $table->timestamps();

            $table->index(['survey_id', 'order']);
            $table->unique(['survey_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
