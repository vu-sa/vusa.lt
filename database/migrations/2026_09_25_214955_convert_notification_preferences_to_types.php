<?php

use App\Enums\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Category × channel preferences become per-type overrides: an unticked email or push box for a
 * category turns that channel off for every type in its section. The in-app column, thread mutes
 * and the separate follow-push switch are folded in or dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('notification_preferences')->orderBy('id')
            ->chunk(200, function ($users): void {
                foreach ($users as $user) {
                    $old = json_decode((string) $user->notification_preferences, true);

                    if (! is_array($old) || array_key_exists('types', $old)) {
                        continue;
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'notification_preferences' => json_encode($this->convert($old)),
                    ]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $old
     * @return array<string, mixed>
     */
    private function convert(array $old): array
    {
        $types = [];

        foreach (NotificationType::configurable() as $type) {
            $channels = $old['channels'][$type->section()->value] ?? [];
            $override = [];

            if (($channels['email_digest'] ?? true) === false && $type->lockedEmail() === null) {
                $override['email'] = 'off';
            }

            $pushOff = $type === NotificationType::FollowedInstitutionActivity
                ? ($old['followed_institutions']['push'] ?? true) === false
                : ($channels['push'] ?? true) === false;

            if ($pushOff && $type->defaultPush()) {
                $override['push'] = false;
            }

            if ($override !== []) {
                $types[$type->value] = $override;
            }
        }

        return array_filter([
            'types' => $types,
            'digest_frequency_hours' => $old['digest_frequency_hours'] ?? null,
            'emails' => $old['digest_emails'] ?? null,
            'muted_until' => $old['muted_until'] ?? null,
            'reminder_settings' => $old['reminder_settings'] ?? null,
        ], fn ($value): bool => $value !== null);
    }

    public function down(): void
    {
        // Irreversible by design: the category matrix cannot represent per-type choices.
    }
};
