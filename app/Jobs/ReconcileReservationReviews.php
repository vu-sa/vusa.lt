<?php

namespace App\Jobs;

use App\Tasks\Synchronizers\ReservationReviewTaskSynchronizer;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconcileReservationReviews implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function handle(ReservationReviewTaskSynchronizer $synchronizer): void
    {
        $synchronizer->reconcileAll();
    }
}
