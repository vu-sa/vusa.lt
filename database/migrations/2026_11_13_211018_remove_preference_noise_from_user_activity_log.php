<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Before `User::getActivitylogOptions()` excluded them, every settings toggle logged a "user updated"
 * entry. Entries that changed nothing else go; mixed ones lose just these keys.
 */
return new class extends Migration
{
    private const array NOISE = ['ui_preferences' => true, 'notification_preferences' => true];

    public function up(): void
    {
        DB::table('activity_log')->where('subject_type', 'user')->where('attribute_changes', 'like', '%_preferences%')
            ->chunkById(500, function ($rows): void {
                $noiseOnly = [];
                foreach ($rows as $row) {
                    $changes = json_decode((string) $row->attribute_changes, true);
                    $attributes = is_array($changes) ? ($changes['attributes'] ?? null) : null;
                    if (! is_array($attributes) || array_intersect_key($attributes, self::NOISE) === []) {
                        continue;
                    }

                    $kept = array_diff_key($attributes, self::NOISE);
                    if ($kept === []) {
                        $noiseOnly[] = $row->id;

                        continue;
                    }

                    $changes['attributes'] = $kept;
                    if (is_array($changes['old'] ?? null)) {
                        $changes['old'] = array_diff_key($changes['old'], self::NOISE);
                    }
                    DB::table('activity_log')->where('id', $row->id)->update(['attribute_changes' => json_encode($changes)]);
                }

                DB::table('activity_log')->whereIn('id', $noiseOnly)->delete();
            });
    }

    public function down(): void
    {
        // Deleted entries recorded nothing but settings toggles; they are not restored.
    }
};
