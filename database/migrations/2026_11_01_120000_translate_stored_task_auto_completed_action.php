<?php

use App\Notifications\TaskAutoCompletedNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Notification payloads keep the label they were sent with, so the old untranslated one stays
     * on every stored row until it is rewritten.
     */
    public function up(): void
    {
        $untranslated = json_encode('View Details');
        $translated = json_encode('Peržiūrėti', JSON_UNESCAPED_UNICODE);

        DB::table('notifications')
            ->where('type', TaskAutoCompletedNotification::class)
            ->update([
                'data' => DB::raw(sprintf(
                    "REPLACE(data, '%s', '%s')",
                    '"label":'.$untranslated,
                    '"label":'.$translated,
                )),
                'updated_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void {}
};
