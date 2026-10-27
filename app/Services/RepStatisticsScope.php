<?php

namespace App\Services;

use App\Enums\ResponsibilityPurpose;
use App\Enums\TenantType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Settings\MeetingSettings;
use App\Settings\RepMetricSettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class RepStatisticsScope
{
    /** @return Builder<Institution> */
    public static function institutions(?array $tenantIds = null): Builder
    {
        $excluded = app(MeetingSettings::class)->getExcludedInstitutionTypeIds();

        $query = Institution::query()->active()
            ->when($tenantIds !== null, fn ($query) => $query->whereIn('tenant_id', $tenantIds))
            ->whereHas('tenant', fn ($query) => $query->whereIn('type', TenantType::representationalValues()))
            ->when($excluded->isNotEmpty(), fn ($query) => $query->whereDoesntHave('types', fn ($types) => $types->whereIn('institution_types.id', $excluded)));

        return app(InstitutionScopeResolver::class)->constrainToScopes($query, app(RepMetricSettings::class)->countedInstitutionScopes());
    }

    /**
     * The in-memory twin of institutions() for an already loaded institution (tenant aside).
     */
    public static function counts(Institution $institution): bool
    {
        if (! $institution->is_active) {
            return false;
        }

        $excluded = app(MeetingSettings::class)->getExcludedInstitutionTypeIds();
        $institution->loadMissing('types');

        return $institution->types->pluck('id')->intersect($excluded)->isEmpty()
            && in_array(app(InstitutionScopeResolver::class)->forInstitution($institution), app(RepMetricSettings::class)->countedInstitutionScopes(), true);
    }

    /** @return Builder<User> */
    public static function representatives(?array $tenantIds, CarbonInterface $at): Builder
    {
        return User::query()->whereHas('dutiables', fn ($terms) => $terms
            ->activeOn($at->toDateString())
            ->whereHas('duty', fn ($duties) => $duties
                ->where('is_active', true)
                ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->whereIn('institution_id', self::institutions($tenantIds)->select('institutions.id'))));
    }

    /**
     * Cancelled tasks are left out: nobody was asked to finish them, so they are neither done nor missed.
     *
     * @return Builder<Task>
     */
    public static function tasks(?array $tenantIds = null): Builder
    {
        $ids = self::institutions($tenantIds)->select('institutions.id');

        return Task::query()->whereNull('tasks.cancelled_at')->whereHasMorph('taskable', [Institution::class, Meeting::class], function ($query, string $type) use ($ids): void {
            if ($type === Institution::class) {
                $query->whereIn('institutions.id', $ids);
            } else {
                $query->whereHas('institutions', fn ($institutions) => $institutions->whereIn('institutions.id', $ids));
            }
        });
    }
}
