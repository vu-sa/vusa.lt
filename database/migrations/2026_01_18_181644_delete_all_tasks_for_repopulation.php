<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time migration to delete all existing tasks for fresh repopulation.
 *
 * This migration is safe to run only once during the initial task system migration.
 * After running, use `php artisan tasks:repopulate --force` to recreate autotasks
 * based on current system state.
 *
 * Note: This is irreversible - completed task history will be lost.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks to allow truncating referenced tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete pivot table entries first
        DB::table('task_user')->truncate();

        // Delete all tasks
        DB::table('tasks')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Increase name column length to accommodate longer institution names
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('name', 500)->change();
        });

        // Note: Run `php artisan tasks:repopulate --force` after this migration
        // to recreate autotasks based on current system state.
    }

    /**
     * Reverse the migrations.
     *
     * This migration is not reversible - task data cannot be restored.
     */
    public function down(): void
    {
        // Revert name column to original length
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('name', 255)->change();
        });
    }
};
