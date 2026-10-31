<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('notification_preferences')
            ->select(['id', 'notification_preferences'])->chunkById(500, function ($users): void {
                foreach ($users as $user) {
                    $preferences = json_decode($user->notification_preferences, true);

                    if (! is_array($preferences) || ! array_key_exists('reminder_settings', $preferences)) {
                        continue;
                    }

                    $settings = $preferences['reminder_settings'];
                    if (is_array($settings)) {
                        if (array_key_exists('task_reminder_days', $settings)) {
                            $settings['task_reminder_days'] = $settings['task_reminder_days'] === [1] ? [1] : [3];
                        }
                        if (($settings['meeting_reminder_hours'] ?? null) === [24, 1]) {
                            $settings['meeting_reminder_hours'] = [24];
                        }
                        $preferences['reminder_settings'] = $settings;
                    }
                    if (in_array($preferences['digest_frequency_hours'] ?? null, [1, 4, 12], true)) {
                        $preferences['digest_frequency_hours'] = 24;
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'notification_preferences' => json_encode($preferences, JSON_THROW_ON_ERROR),
                        'updated_at' => DB::raw('updated_at'),
                    ]);
                }
            });
    }

    public function down(): void {}
};
