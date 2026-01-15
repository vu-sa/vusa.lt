<?php

namespace App\Listeners\Approval;

use App\Events\ApprovalDecisionMade;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

/**
 * Automatically completes tasks with action_type='approval' when an approval decision is made.
 */
class CompleteTasksOnApproval implements ShouldQueue
{
    public function handle(ApprovalDecisionMade $event): void
    {
        $approvable = $event->approvable;

        // Get the morph class name for the approvable
        $morphClass = $approvable->getMorphClass();

        // Also check for snake_case version (e.g., 'reservation_resource')
        $snakeCaseClass = Str::snake(class_basename($approvable));

        // Find tasks linked to this approvable with action_type='approval' and mark them completed
        Task::query()
            ->where('action_type', 'approval')
            ->whereNull('completed_at')
            ->where(function ($query) use ($approvable, $morphClass, $snakeCaseClass) {
                $query->where(function ($q) use ($approvable, $morphClass) {
                    $q->where('taskable_type', $morphClass)
                        ->where('taskable_id', $approvable->getKey());
                })->orWhere(function ($q) use ($approvable, $snakeCaseClass) {
                    $q->where('taskable_type', $snakeCaseClass)
                        ->where('taskable_id', $approvable->getKey());
                });
            })
            ->update(['completed_at' => now()]);

        // For ReservationResource, also check tasks on the Reservation
        if (method_exists($approvable, 'reservation')) {
            $reservation = $approvable->reservation;

            if ($reservation) {
                $reservationMorphClass = $reservation->getMorphClass();
                $reservationSnakeCase = Str::snake(class_basename($reservation));

                Task::query()
                    ->where('action_type', 'approval')
                    ->whereNull('completed_at')
                    ->where(function ($query) use ($reservation, $reservationMorphClass, $reservationSnakeCase) {
                        $query->where(function ($q) use ($reservation, $reservationMorphClass) {
                            $q->where('taskable_type', $reservationMorphClass)
                                ->where('taskable_id', $reservation->getKey());
                        })->orWhere(function ($q) use ($reservation, $reservationSnakeCase) {
                            $q->where('taskable_type', $reservationSnakeCase)
                                ->where('taskable_id', $reservation->getKey());
                        });
                    })
                    ->update(['completed_at' => now()]);
            }
        }
    }
}
