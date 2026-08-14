<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The trainings, programmes and memberships features were merged in December 2024
 * and never adopted — no production data, no role held their permissions, and no
 * other table references them. This drops all fourteen tables.
 *
 * The original create-migrations are left in place so applied history stays
 * intact; on a fresh build the tables are created, altered and then dropped here.
 *
 * `down()` recreates the schema faithfully, including the `deleted_at` column
 * that 2026_07_27_005816_add_soft_deletes_to_content_tables added to `trainings`,
 * so a rollback lands on the same shape the drop found.
 */
return new class extends Migration
{
    /**
     * Children first — every table is dropped before the ones it points at.
     *
     * @var array<int, string>
     */
    private const TABLES = [
        'programme_block_part',
        'programme_day_elements',
        'programme_blocks',
        'programme_parts',
        'programme_sections',
        'programme_days',
        'programmables',
        'programmes',
        'membership_user',
        'memberships',
        'trainables',
        'training_tasks',
        'training_user',
        'trainings',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::dropIfExists($tableName);
        }

        // ModelPermissionSeeder generated these from ModelEnum, which no longer
        // carries a TRAINING or MEMBERSHIP case, so nothing prunes them otherwise.
        DB::table('permissions')
            ->where('name', 'like', 'trainings.%')
            ->orWhere('name', 'like', 'memberships.%')
            ->delete();
    }

    public function down(): void
    {
        $this->recreateTrainingTables();
        $this->recreateMembershipTables();
        $this->recreateProgrammeTables();
    }

    private function recreateTrainingTables(): void
    {
        Schema::create('trainings', function (Blueprint $table): void {
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
            $table->softDeletes();
        });

        Schema::create('trainables', function (Blueprint $table): void {
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

        Schema::create('training_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('training_id')->references('id')->on('trainings');
            $table->json('name');
            $table->json('description')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('training_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('training_id')->references('id')->on('trainings');
            $table->foreignUlid('user_id')->references('id')->on('users');
            $table->string('responsability')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    private function recreateMembershipTables(): void
    {
        Schema::create('memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->json('name');
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('membership_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    private function recreateProgrammeTables(): void
    {
        Schema::create('programmes', function (Blueprint $table): void {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->date('start_date');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_id')->constrained()->onDelete('cascade');
            $table->json('title');
            $table->json('description')->nullable();
            $table->integer('order');
            $table->dateTime('start_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_parts', function (Blueprint $table): void {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('instructor')->nullable();
            $table->integer('duration');
            $table->time('start_time')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_sections', function (Blueprint $table): void {
            $table->id();
            $table->json('title');
            $table->integer('duration');
            $table->time('start_time')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_section_id')->constrained()->onDelete('cascade');
            $table->json('title');
            $table->json('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programmables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_id')->constrained()->onDelete('cascade');
            $table->ulid('programmable_id');
            $table->string('programmable_type');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_day_elements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_day_id')->constrained()->onDelete('cascade');
            $table->morphs('elementable');
            $table->integer('order');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('programme_block_part', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_block_id')->constrained()->onDelete('cascade');
            $table->foreignId('programme_part_id')->constrained()->onDelete('cascade');
            $table->integer('order');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }
};
