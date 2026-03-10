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
        Schema::create('problems', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->text('solution')->nullable();
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('occurred_at');
            $table->date('resolved_at')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problems');
    }
};
