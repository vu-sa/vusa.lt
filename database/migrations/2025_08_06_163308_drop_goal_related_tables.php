<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove goal-related permissions
        Permission::where('name', 'like', 'goals.%')->delete();
        Permission::where('name', 'like', 'goalGroups.%')->delete();

        Schema::dropIfExists('goal_matter');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('goal_groups');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate goal_groups table
        Schema::create('goal_groups', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();
        });

        // Recreate goals table
        Schema::create('goals', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('group_id', 26)->nullable()->index('goals_group_id_foreign');
            $table->char('tenant_id', 26);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();
        });

        // Recreate goal_matter pivot table
        Schema::create('goal_matter', function (Blueprint $table) {
            $table->char('goal_id', 26);
            $table->char('matter_id', 26)->index('goal_matter_matter_id_foreign');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->primary(['goal_id', 'matter_id']);
        });

        // Add foreign key constraints
        Schema::table('goals', function (Blueprint $table) {
            $table->foreign(['group_id'])->references(['id'])->on('goal_groups');
            $table->foreign(['tenant_id'])->references(['id'])->on('tenants');
        });

        Schema::table('goal_matter', function (Blueprint $table) {
            $table->foreign(['goal_id'])->references(['id'])->on('goals')->onDelete('CASCADE');
            $table->foreign(['matter_id'])->references(['id'])->on('matters');
        });
    }
};
