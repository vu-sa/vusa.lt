<?php

namespace App\Observers;

use App\Models\Calendar;
use App\Models\Duty;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionSecretary;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\Task;
use App\Models\Vote;
use App\Services\RepStatisticsCache;
use Illuminate\Database\Eloquent\Model;

class RepStatisticsObserver
{
    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    public function restored(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        // Files hang off many models; only a meeting's show in the dashboard (report and protocol flags).
        if ($model instanceof FileableFile && $model->fileable_type !== (new Meeting)->getMorphClass()) {
            return;
        }

        RepStatisticsCache::invalidate(tenantIds: $this->tenantIds($model));
    }

    /**
     * The padaliniai a change can show up in; null (every entry) for models without a clear one.
     *
     * @return list<int>|null
     */
    private function tenantIds(Model $model): ?array
    {
        $ids = match (true) {
            $model instanceof Institution => [$model->tenant_id, $model->getOriginal('tenant_id')],
            $model instanceof InstitutionCheckIn, $model instanceof InstitutionActivityRequest, $model instanceof InstitutionSecretary => $this->institutionTenants([$model->institution_id]),
            $model instanceof Duty => $this->institutionTenants([$model->institution_id, $model->getOriginal('institution_id')]),
            $model instanceof Dutiable => $this->institutionTenants(Duty::withTrashed()->whereKey([$model->duty_id, $model->getOriginal('duty_id')])->pluck('institution_id')->all()),
            $model instanceof Meeting => $this->meetingTenants([$model->id]),
            $model instanceof Calendar => $this->meetingTenants([$model->meeting_id, $model->getOriginal('meeting_id')]),
            $model instanceof FileableFile => $this->meetingTenants([$model->fileable_id]),
            $model instanceof AgendaItem => $this->meetingTenants([$model->meeting_id]),
            $model instanceof Vote => $this->meetingTenants(AgendaItem::query()->whereKey($model->agenda_item_id)->pluck('meeting_id')->all()),
            $model instanceof Task => $this->taskTenants($model),
            default => [],
        };

        $ids = array_values(array_unique(array_map(intval(...), array_filter($ids))));

        return $ids === [] ? null : $ids;
    }

    /** @return list<int|string|null> */
    private function taskTenants(Task $task): array
    {
        return match ($task->taskable_type) {
            (new Institution)->getMorphClass() => $this->institutionTenants([$task->taskable_id]),
            (new Meeting)->getMorphClass() => $this->meetingTenants([$task->taskable_id]),
            default => [],
        };
    }

    /**
     * @param  list<int|string|null>  $institutionIds
     * @return list<int|string|null>
     */
    private function institutionTenants(array $institutionIds): array
    {
        return Institution::withTrashed()->whereIn('id', array_filter($institutionIds))->pluck('tenant_id')->all();
    }

    /**
     * @param  list<int|string|null>  $meetingIds
     * @return list<int|string|null>
     */
    private function meetingTenants(array $meetingIds): array
    {
        return Institution::withTrashed()
            ->whereHas('meetings', fn ($query) => $query->withTrashed()->whereIn('meetings.id', array_filter($meetingIds)))
            ->pluck('tenant_id')
            ->all();
    }
}
