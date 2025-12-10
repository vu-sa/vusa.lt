<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove tasks that reference the old App\Models\Doing model which no longer exists.
     * This model was likely renamed to Duty or removed entirely.
     */
    public function up(): void
    {
        // First, detach any user associations for these tasks
        DB::table('task_user')
            ->whereIn('task_id', function ($query) {
                $query->select('id')
                    ->from('tasks')
                    ->where('taskable_type', 'App\\Models\\Doing');
            })
            ->delete();

        // Then delete the tasks themselves
        DB::table('tasks')
            ->where('taskable_type', 'App\\Models\\Doing')
            ->delete();
    }

    /**
     * Reverse the migrations.
     * 
     * Cannot restore deleted tasks - this is a one-way migration.
     */
    public function down(): void
    {
        // Cannot restore deleted data
    }
};
