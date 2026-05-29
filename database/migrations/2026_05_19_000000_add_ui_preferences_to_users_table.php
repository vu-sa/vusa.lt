<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ui_preferences JSON structure:
     * {
     *   "sidebar": {
     *     "sections": {
     *       "quick_actions": true,
     *       "followed_institutions": true,
     *       "start_fm": true,
     *       "secondary": true,
     *       "recently_visited": true
     *     }
     *   },
     *   "recent_pages": [
     *     {"route": "meetings.index", "params": {}, "visited_at": "ISO8601"}
     *   ]
     * }
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('ui_preferences')->nullable()->after('notification_preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ui_preferences');
        });
    }
};
