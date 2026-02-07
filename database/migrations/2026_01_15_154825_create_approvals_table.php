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
        // Approval flows define multi-step approval configurations
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('flowable_type')->nullable(); // Polymorphic - can be null for global flows
            $table->char('flowable_id', 26)->nullable();
            $table->json('steps'); // Array of step configs: [{role/permission, required_count, deadline_days}]
            $table->boolean('is_sequential')->default(true); // Sequential vs parallel steps
            $table->unsignedInteger('escalation_days')->nullable(); // Days before escalation reminder
            $table->timestamps();

            $table->index(['flowable_type', 'flowable_id']);
        });

        // Individual approval records
        Schema::create('approvals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('approvable_type');
            $table->char('approvable_id', 26);
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('decision'); // ApprovalDecision enum: approved, rejected, cancelled
            $table->unsignedInteger('step')->default(1); // Which step in multi-step flow
            $table->text('notes')->nullable(); // Optional notes/reason
            $table->timestamps();
            $table->softDeletes();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['approvable_type', 'approvable_id', 'step']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('approval_flows');
    }
};
