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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->change();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->json('name');
            $table->json('description');
            $table->string('address')->nullable();
            $table->text('meeting_url')->nullable();
            $table->string('image')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->foreignUlid('organizer_id')->references('id')->on('users');
            $table->foreignUlid('institution_id')->references('id')->on('institutions');
            $table->foreignUlid('form_id')->nullable()->references('id')->on('forms');
            $table->unsignedInteger('max_participants')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('trainables', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('training_id')->references('id')->on('trainings');
            $table->string('trainable_type');
            $table->ulid('trainable_id');
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->integer('quota')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('training_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('training_id')->references('id')->on('trainings');
            $table->json('name');
            $table->json('description')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('training_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('training_id')->references('id')->on('trainings');
            $table->foreignUlid('user_id')->references('id')->on('users');
            $table->string('responsability')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_user');
        Schema::dropIfExists('task_training');
        Schema::dropIfExists('trainables');
        Schema::dropIfExists('trainings');
    }
};
