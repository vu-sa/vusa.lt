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
        Schema::create('planning_processes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('tenant_id');
            $table->smallInteger('academic_year_start');
            $table->ulid('moderator_user_id')->nullable();
            $table->tinyInteger('current_stage')->default(1);

            // Stage I: Expectations
            $table->text('expectations_text')->nullable();
            $table->timestamp('expectations_submitted_at')->nullable();

            // Stage II: Meetings
            $table->text('meeting_1_notes')->nullable();
            $table->date('meeting_1_date')->nullable();
            $table->text('meeting_2_notes')->nullable();
            $table->date('meeting_2_date')->nullable();
            $table->ulid('selected_problem_id')->nullable();
            $table->text('goal_text')->nullable();
            $table->timestamp('goal_approved_at')->nullable();

            // Stage III: Documents
            $table->timestamp('tip_approved_at')->nullable();
            $table->ulid('tip_approved_by')->nullable();
            $table->timestamp('mvp_approved_at')->nullable();
            $table->ulid('mvp_approved_by')->nullable();

            // Stage V: Reflection
            $table->text('reflection_text')->nullable();
            $table->timestamp('reflection_submitted_at')->nullable();

            // Meta
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('moderator_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('selected_problem_id')->references('id')->on('problems')->nullOnDelete();
            $table->foreign('tip_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('mvp_approved_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['tenant_id', 'academic_year_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_processes');
    }
};
