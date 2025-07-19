<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Laravel Pulse package has been removed from this application.
        // These tables were previously created by Laravel Pulse but are no longer needed.
        // If you need to recreate these tables, reinstall the Laravel Pulse package.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No tables to drop since they were never created by this migration
        // after the Pulse package was removed.
    }
};
