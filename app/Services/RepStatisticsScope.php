<?php

namespace App\Services;

use App\Enums\ResponsibilityPurpose;
use App\Enums\TenantType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Settings\MeetingSettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class RepStatisticsScope
{
    /** @return Builder<Institution> */
    public static function institutions(?array $tenantIds = null): Builder
    {
        $excluded = app(MeetingSettings::class)->getExcludedInstitutionTypeIds();

        return Institution::query()
            ->when($tenantIds !== null, fn ($query) => $query->whereIn('tenant_id', $tenantIds))
            ->whereHas('tenant', fn ($query) => $query->whereIn('type', TenantType::representationalValues()))
            ->when($excluded->isNotEmpty(), fn ($query) => $query->whereDoesntHave('types', fn ($types) => $types->whereIn('institution_types.id', $excluded)));
    }

    /** @return Builder<User> */
    public static function representatives(?array $tenantIds, CarbonInterface $at): Builder
    {
        return User::query()->whereHas('dutiables', fn ($terms) => $terms
            ->activeOn($at->toDateString())
            ->whereHas('duty', fn ($duties) => $duties
                ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->whereIn('institution_id', self::institutions($tenantIds)->select('institutions.id'))));
    }

    /** @return Builder<Task> */
    public static function tasks(?array $tenantIds = null): Builder
    {
        $ids = self::institutions($tenantIds)->select('institutions.id');

        return Task::query()->whereHasMorph('taskable', [Institution::class, Meeting::class], function ($query, string $type) use ($ids): void {
            if ($type === Institution::class) {
                $query->whereIn('institutions.id', $ids);
            } else {
                $query->whereHas('institutions', fn ($institutions) => $institutions->whereIn('institutions.id', $ids));
            }
        });
    }
}
