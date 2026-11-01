<?php

namespace App\Actions;

use App\Mail\NotificationDigest;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildNotificationDigest
{
    /** @param Collection<int, NotificationDigestQueue> $items */
    public function execute(User $user, Collection $items): NotificationDigest
    {
        return new NotificationDigest($user, $items->groupBy('category')
            ->map(fn (Collection $categoryItems): array => $categoryItems->pluck('data')->all())->all());
    }
}
