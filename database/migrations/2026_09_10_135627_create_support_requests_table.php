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
        Schema::create('support_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('support_service_id')->constrained('support_services')->cascadeOnDelete();
            $table->foreignId('support_request_type_id')->constrained('support_request_types')->cascadeOnDelete();
            $table->foreignId('support_request_area_id')->constrained('support_request_areas')->cascadeOnDelete();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('visibility')->default('private');
            $table->string('status')->default('new');
            $table->string('title');
            $table->text('description');
            $table->text('context_url')->nullable();
            $table->text('selected_text')->nullable();
            $table->string('locale', 5)->default('lt');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_by', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['visibility', 'status', 'created_at']);
        });

        Schema::create('role_support_request', function (Blueprint $table): void {
            $table->foreignUlid('support_request_id')->constrained('support_requests')->cascadeOnDelete();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['support_request_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_support_request');
        Schema::dropIfExists('support_requests');
    }
};
