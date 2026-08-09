<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The reusable question bank.
 *
 * A template is a blueprint only. Attaching one to a survey copies its content into
 * survey_questions rather than referencing it, so editing a template next year cannot
 * retroactively change a survey that has already been pushed to LimeSurvey.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_question_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Null means the template is available to every tenant.
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();

            $table->json('group_name');
            $table->string('title', 20)->comment('LimeSurvey question code, e.g. Q01');
            $table->string('type', 5);
            $table->json('question');
            $table->json('help')->nullable();
            $table->json('options')->nullable()->comment('[{code, label: {lt, en}}]');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_question_templates');
    }
};
