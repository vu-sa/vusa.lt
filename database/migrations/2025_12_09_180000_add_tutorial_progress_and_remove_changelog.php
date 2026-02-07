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
        // Add tutorial_progress JSON column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->json('tutorial_progress')->nullable()->after('last_changelog_check');
        });

        // Remove last_changelog_check column from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_changelog_check');
        });

        // Drop changelog_items table
        Schema::dropIfExists('changelog_items');

        Permission::where('name', 'like', 'changelogItems.%')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate changelog_items table
        Schema::create('changelog_items', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->dateTime('date');
            $table->json('description');
            $table->char('permission_id', 26)->nullable()->index('changelog_items_permission_id_foreign');
        });

        // Add back last_changelog_check column
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('last_changelog_check')->nullable()->after('last_action');
        });

        // Remove tutorial_progress column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tutorial_progress');
        });
    }
};
