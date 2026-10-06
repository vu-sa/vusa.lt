<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StagingAccountScrubber
{
    public function scrub(bool $preserveEmails): int
    {
        if ($preserveEmails) {
            DB::table('users')->update([
                'phone' => null,
                'remember_token' => null,
            ]);

            return 0;
        }

        $configured = config('app.staging_refresh.email_allowlist', '');
        $allowlist = is_string($configured) ? explode(',', $configured) : (array) $configured;
        $allowlist = array_values(array_filter(array_map(trim(...), $allowlist)));
        $scrubbed = 0;

        DB::table('users')
            ->select('id', 'email')
            ->whereNotIn('email', $allowlist)
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$scrubbed): void {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'email' => 'user'.$user->id.'@staging.invalid',
                        'phone' => null,
                        'remember_token' => null,
                    ]);
                    $scrubbed++;
                }
            });

        return $scrubbed;
    }
}
