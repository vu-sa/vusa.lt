<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * The goals (Tikslai) pilot, see App\Support\Experiments\GoalsExperiment. Everything it adds is
 * here, so rolling this one migration back removes the experiment's data and permissions.
 */
return new class extends Migration
{
    /** ModelPermissionSeeder only adds permissions when it is re-run, so the pilot's are created here. */
    private const array PERMISSIONS = [
        'goals.create.padalinys',
        'goals.create.*',
        'goals.read.padalinys',
        'goals.read.*',
        'goals.update.padalinys',
        'goals.update.*',
        'goals.delete.padalinys',
        'goals.delete.*',
    ];

    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignUlid('cadence_id')->nullable()->constrained('cadences')->nullOnDelete();
            $table->foreignUlid('responsible_duty_id')->nullable()->constrained('duties')->nullOnDelete();
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('expected_result')->nullable();
            $table->json('evaluation')->nullable();
            $table->string('status')->default('planned')->index();
            $table->boolean('is_public')->default(false);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('goal_problem', function (Blueprint $table) {
            $table->foreignUlid('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignUlid('problem_id')->constrained('problems')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['goal_id', 'problem_id']);
        });

        Schema::create('steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('goal_id')->nullable()->constrained('goals')->cascadeOnDelete();
            $table->foreignUlid('problem_id')->nullable()->constrained('problems')->cascadeOnDelete();
            $table->json('title');
            $table->json('description')->nullable();
            $table->date('happened_on');
            // What the step points to, besides its text: where it was discussed, a document or a link.
            $table->foreignUlid('agenda_item_id')->nullable()->constrained('agenda_items')->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('url', 2048)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['goal_id', 'happened_on']);
            $table->index(['problem_id', 'happened_on']);
        });

        // Who did the step; the recorder is `steps.created_by`.
        Schema::create('step_user', function (Blueprint $table) {
            $table->foreignUlid('step_id')->constrained('steps')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['step_id', 'user_id']);
        });

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('step_user');
        Schema::dropIfExists('steps');
        Schema::dropIfExists('goal_problem');
        Schema::dropIfExists('goals');

        Permission::query()->whereIn('name', self::PERMISSIONS)->get()->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
