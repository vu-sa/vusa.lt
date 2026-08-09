<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One survey: drafted and approved here, delivered by LimeSurvey.
 *
 * No response data is stored. `response_stats` holds aggregate counters pulled back from
 * LimeSurvey so the admin can see uptake without student answers ever leaving LimeSurvey.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->json('name');
            $table->json('description')->nullable();
            $table->json('welcome_text')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_anonymous')->default(true);

            $table->string('status', 30)->default('draft');

            // Set once the survey exists in LimeSurvey. Its presence is what makes the
            // record read-only, and what makes a job retry reconcile instead of re-import.
            $table->unsignedInteger('limesurvey_survey_id')->nullable()->unique();
            $table->string('limesurvey_url')->nullable();

            $table->string('sync_status', 20)->nullable();
            $table->text('sync_error_message')->nullable();

            $table->json('response_stats')->nullable();
            $table->timestamp('stats_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
