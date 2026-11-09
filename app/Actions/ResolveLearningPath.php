<?php

namespace App\Actions;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Enums\LearningLayer;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\LearningStepProgress;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningStep;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use App\Services\Learning\SystemSteps\WriteHandoverDetector;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResolveLearningPath
{
    public static function execute(User $user): LearningPath
    {
        return self::forUsers(collect([$user]))->get($user->id);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<string, LearningPath>
     */
    public static function forUsers(Collection $users): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }
        // Fresh load: ModelAuthorizer may have left a partial `authorization_duties` on the same instance.
        (new EloquentCollection($users->all()))->load([
            'authorization_duties.institution.types',
            'authorization_duties.types',
            'authorization_duties.predecessors' => fn ($query) => $query->withTrashed(),
        ]);
        $duties = $users->flatMap(fn (User $user) => $user->authorization_duties)->unique('id')->values();
        $repIds = Duty::query()->whereIn('id', $duties->pluck('id'))
            ->where(fn ($q) => $q->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->orWhere(fn ($c) => $c->holdingPurpose(ResponsibilityPurpose::StudentRepCoordination)))->pluck('id');
        $cadences = ResolveCadenceForDuty::forDuties($duties);
        $institutionTypes = self::ancestry(InstitutionType::all());
        $dutyTypes = self::ancestry(DutyType::all());
        $steps = LearningStep::query()->active()->ordered()->with(['sources.document.institution.tenant', 'institutionType', 'dutyType', 'tenant'])->withCount('comments')->get();
        $progress = LearningStepProgress::query()->whereIn('user_id', $users->pluck('id'))->get()->groupBy('user_id');
        $detections = [];
        foreach (SystemStepRegistry::definitions() as $key => $definition) {
            if ($definition['detector'] !== null && $key !== 'write_handover') {
                $detections[$key] = app($definition['detector'])->doneFor($users->pluck('id')->values())->flip();
            }
        }
        $received = GetReceivedHandoverNotes::forUsers($users);
        $handoverRepIds = Duty::query()->whereIn('id', $repIds)->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)->pluck('id');
        $written = WriteHandoverDetector::completionDates($users, $handoverRepIds);
        $paths = collect();
        foreach ($users as $user) {
            $userDuties = $user->authorization_duties;
            $repDuties = $userDuties->whereIn('id', $repIds)->sortBy(fn (Duty $duty) => $duty->pivot->start_date);
            $first = $repDuties->first();
            $termStart = $first?->pivot->start_date;
            $end = $first?->pivot->end_date ?? ($first ? ($cadences[$first->id] ?? null)?->end_date : null);
            $institutionTypeIds = $userDuties->flatMap(fn (Duty $duty) => $duty->institution?->types->pluck('id') ?? collect())
                ->flatMap(fn (int $id): array => $institutionTypes[$id] ?? [$id])->unique();
            $dutyTypeIds = $userDuties->flatMap(fn (Duty $duty) => $duty->types->pluck('id'))->flatMap(fn (int $id): array => $dutyTypes[$id] ?? [$id])->unique();
            $tenantIds = $repDuties->pluck('institution.tenant_id')->filter()->unique();
            $userProgress = $progress->get($user->id, collect())->keyBy('learning_step_id');
            $resolved = collect();
            $meetingPrep = collect();
            $writtenAt = $written->get($user->id);
            foreach ($steps as $step) {
                $included = self::inAudience($step, $repDuties, $institutionTypeIds, $dutyTypeIds, $tenantIds);
                if (! $included || ($step->key === 'read_handover' && $received->get($user->id, collect())->isEmpty())) {
                    continue;
                }
                if ($step->kind === LearningStepKind::MeetingPrep) {
                    $meetingPrep->push(new ResolvedLearningStep($step));

                    continue;
                }
                $resolved->push(self::resolveStep($step, $userProgress->get($step->id), $termStart,
                    $step->is_system && ($step->key === 'write_handover' ? $writtenAt !== null : isset($detections[$step->key][$user->id])),
                    $writtenAt));
            }
            $paths->put($user->id, new LearningPath($user, self::stage($first !== null, $termStart, $end), $termStart, $resolved, $meetingPrep));
        }

        return $paths;
    }

    /**
     * @param  Collection<int, Duty>  $repDuties
     * @param  Collection<int, int>  $institutionTypes
     * @param  Collection<int, int>  $dutyTypes
     * @param  Collection<int, int>  $tenants
     */
    private static function inAudience(LearningStep $step, Collection $repDuties, Collection $institutionTypes, Collection $dutyTypes, Collection $tenants): bool
    {
        return match ($step->layer) {
            LearningLayer::Platform => true,
            LearningLayer::Foundation => $repDuties->isNotEmpty(),
            LearningLayer::InstitutionType => $institutionTypes->contains($step->institution_type_id),
            LearningLayer::DutyType => $dutyTypes->contains($step->duty_type_id),
            LearningLayer::Tenant => $tenants->contains($step->tenant_id),
        };
    }

    /** Incoming reps prepare before their term starts, so per-term work done in the lead-up still counts. */
    private const int TERM_LEAD_IN_DAYS = 90;

    private static function completionIsCurrent(LearningStep $step, ?Carbon $date, ?Carbon $termStart): bool
    {
        return $date !== null && (! $step->per_term || $termStart === null || $date->gte($termStart->copy()->subDays(self::TERM_LEAD_IN_DAYS)));
    }

    private static function resolveStep(LearningStep $step, ?LearningStepProgress $progress, ?Carbon $termStart, bool $detected, ?Carbon $writtenAt): ResolvedLearningStep
    {
        $completedAt = self::completionIsCurrent($step, $progress?->completed_at, $termStart) ? $progress->completed_at : null;
        $state = 'pending';
        if ($completedAt !== null) {
            $state = $progress->status === LearningProgressStatus::Done ? 'done' : ($step->importance->isSkippable() ? 'skipped' : 'pending');
        }
        if ($detected && $step->key === 'write_handover') {
            $detected = self::completionIsCurrent($step, $writtenAt, $termStart);
            if ($detected) {
                $completedAt = $writtenAt;
            }
        }
        if ($detected) {
            return new ResolvedLearningStep($step, 'done', true, $completedAt);
        }
        if (self::sourceUpdated($step, $state, $completedAt)) {
            $state = 'updated';
        }

        return new ResolvedLearningStep($step, $state, false, $completedAt);
    }

    private static function sourceUpdated(LearningStep $step, string $state, ?Carbon $completedAt): bool
    {
        $modified = $step->sourceFor(app()->getLocale())?->document?->sharepoint_modified_at;

        return $state === 'done' && $step->kind === LearningStepKind::Knowledge && $completedAt !== null && $modified !== null && $modified->gt($completedAt);
    }

    /**
     * @param  EloquentCollection<int, InstitutionType>|EloquentCollection<int, DutyType>  $types
     * @return array<int, list<int>>
     */
    private static function ancestry(EloquentCollection $types): array
    {
        $byId = $types->keyBy('id');
        foreach ($types as $type) {
            $type->setRelation('parent', $byId->get($type->parent_id));
        }
        $result = [];
        foreach ($types as $type) {
            $result[$type->id] = $type->getParentsAndSelf()->pluck('id')->all();
        }

        return $result;
    }

    private static function stage(bool $isRep, ?Carbon $start, ?Carbon $end): ?LearningStage
    {
        if (! $isRep) {
            return null;
        }
        if ($start?->isFuture()) {
            return LearningStage::BeforeStart;
        }
        if ($start !== null && today()->lte($start->copy()->addDays(60))) {
            return LearningStage::FirstMonths;
        }
        if ($end !== null && today()->gte($end->copy()->subDays(45))) {
            return LearningStage::TermEnd;
        }

        return LearningStage::WholeTerm;
    }
}
