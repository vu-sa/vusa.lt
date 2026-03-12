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
        Schema::create('study_sets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('study_set_courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('study_set_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->unsignedInteger('order')->default(0);
            $table->string('semester');
            $table->unsignedInteger('credits');
            $table->boolean('is_visible')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('lecturer_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('study_set_course_id')->constrained()->cascadeOnDelete();
            $table->json('lecturer');
            $table->json('comment');
            $table->boolean('is_visible')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_reviews');
        Schema::dropIfExists('study_set_courses');
        Schema::dropIfExists('study_sets');
    }
};
