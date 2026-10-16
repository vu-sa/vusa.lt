<?php

namespace App\Services;

use App\Events\DutiableChanged;
use App\Listeners\HandleDutiableChange;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstitutionDeactivationService
{
    /** @return Builder<Dutiable> */
    public function affectedTerms(Institution $institution): Builder
    {
        $ownTerms = Dutiable::query()->whereIn('duty_id', Duty::withTrashed()
            ->where('institution_id', $institution->id)->select('id'));

        return Dutiable::query()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->current()
            ->where(fn ($query) => $query
                ->whereIn('id', (clone $ownTerms)->select('id'))
                ->orWhereIn('via_dutiable_id', $ownTerms->select('id')));
    }

    /** @return array{endingCount: int, blockers: list<array{duty: string, startDate: string}>, effectiveEndDate: string} */
    public function summary(Institution $institution): array
    {
        $terms = $this->affectedTerms($institution)->with(['duty' => fn ($query) => $query->withTrashed()])->get()->toBase();

        return [
            'endingCount' => $terms->where('start_date', '<', today())->count(),
            'blockers' => $terms->filter(fn (Dutiable $term) => $term->start_date->gte(today()))
                ->map(fn (Dutiable $term): array => [
                    'duty' => $term->duty?->getTranslation('name', app()->getLocale()) ?? '',
                    'startDate' => $term->start_date->toDateString(),
                ])->values()->all(),
            'effectiveEndDate' => today()->subDay()->toDateString(),
        ];
    }

    public function assertCanDeactivate(Institution $institution): void
    {
        if ($this->affectedTerms($institution)->whereDate('start_date', '>=', today())->exists()) {
            throw ValidationException::withMessages(['is_active' => __('forms.helpers.institution_deactivation_blocked')]);
        }
    }

    /** @return list<string> */
    public function closeTerms(Institution $institution): array
    {
        $terms = $this->affectedTerms($institution)->orderBy('id')->lockForUpdate()->get();
        $this->assertCanDeactivate($institution);

        // Derived rows close first so synchronous source listeners cannot overwrite their history.
        app(DutyAssignmentService::class)->endDateDutiables(
            Dutiable::whereKey($terms->modelKeys())->orderByDesc('via_dutiable_id'),
            today()->subDay(),
            institutionDeactivation: true,
        );

        DB::afterCommit(function () use ($terms): void {
            foreach ($terms->unique('dutiable_id') as $term) {
                app(HandleDutiableChange::class)->handle(new DutiableChanged($term));
            }
        });

        return $terms->modelKeys();
    }
}
