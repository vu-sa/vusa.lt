<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration clears all legacy notifications as part of the notification system refactor.
     * The new system uses a standardized data format that is incompatible with legacy notifications.
     */
    public function up(): void
    {
        // Clear all existing notifications for a fresh start with the new system
        DB::table('notifications')->truncate();

        // Also clear any pending digest items if the table exists
        if (Schema::hasTable('notification_digest_queue')) {
            DB::table('notification_digest_queue')->truncate();
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration cannot be reversed as the data is permanently deleted.
     */
    public function down(): void
    {
        // Data cannot be restored - this is a one-way migration
    }
};
