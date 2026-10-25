<?php

namespace App\Listeners;

use App\Events\DutiableChanged;
use App\Events\SearchRelationChanged;
use App\Jobs\ReconcileReservationReviews;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\Pivots\DutyDutyType;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Task;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;

class QueueReservationReviewSync
{
    public function handle(DutiableChanged|SearchRelationChanged $event): void
    {
        if ($event instanceof SearchRelationChanged && ! $event->model instanceof Duty && ! $event->model instanceof Institution && ! $event->model instanceof Reservation) {
            return;
        }

        $this->queue();
    }

    public function saved(Model $model): void
    {
        $fields = match (true) {
            $model instanceof ResponsibilityGrant => ['role_id', 'grantee_type', 'grantee_id', 'scope_type', 'scope_id'],
            $model instanceof Role => ['purpose'],
            $model instanceof Duty => ['institution_id'],
            $model instanceof DutyType => ['parent_id'],
            $model instanceof DutyDutyType => ['duty_id', 'duty_type_id'],
            $model instanceof Institution, $model instanceof Resource => ['tenant_id'],
            default => [],
        };

        if ($model->wasChanged($fields) || (($model instanceof ResponsibilityGrant || $model instanceof DutyDutyType) && $model->wasRecentlyCreated)) {
            $this->queue();
        }
    }

    public function deleted(Model $model): void
    {
        $this->queue();
    }

    public function restored(Model $model): void
    {
        $this->queue();
    }

    private function queue(): void
    {
        if (ReservationResource::query()->where('state', 'created')->exists()
            || Task::query()->where('action_type', ActionType::Approval)->whereNull('completed_at')->exists()) {
            ReconcileReservationReviews::dispatch()->afterCommit();
        }
    }
}
