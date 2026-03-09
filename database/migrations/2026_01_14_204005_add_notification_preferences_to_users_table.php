<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * notification_preferences JSON structure:
     * {
     *   "channels": {
     *     "comment": {"in_app": true, "push": true, "email_digest": true},
     *     "task": {"in_app": true, "push": true, "email_digest": true},
     *     ...
     *   },
     *   "digest_frequency_hours": 4,  // 1, 4, 12, 24
     *   "muted_until": null,  // ISO datetime or null
     *   "muted_threads": [],  // [{model_class: "Reservation", model_id: "...", until: "..."}]
     *   "reminder_settings": {
     *     "task_reminder_days": [7, 3, 1],
     *     "meeting_reminder_hours": [24, 1]
     *   }
     * }
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('tutorial_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
