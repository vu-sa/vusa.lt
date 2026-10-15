<?php

namespace App\Services\Typesense;

use Closure;
use Illuminate\Support\Facades\Cache;

class MeetingSearchLock
{
    /** @var array<string, true> */
    private array $held = [];

    public function run(string $meetingId, Closure $callback): mixed
    {
        if (isset($this->held[$meetingId])) {
            return $callback();
        }

        return Cache::lock('meeting-search:'.$meetingId, 120)->block(15, function () use ($meetingId, $callback): mixed {
            $this->held[$meetingId] = true;

            try {
                return $callback();
            } finally {
                unset($this->held[$meetingId]);
            }
        });
    }
}
