<?php

namespace App\Actions;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\LearningAudience;
use App\Models\LearningProgress;
use App\Models\LearningStep;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningItem;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use App\Support\MorphMap;
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
            'authorization_duties.roles',
            'authorization_duties.predecessors' => fn ($query) => $query->withTrashed(),
        ]);
        $duties = $users->flatMap(fn (User $user) => $user->authorization_duties)->unique('id')->values();
        $repIds = Duty::query()->whereIn('id', $duties->pluck('id'))
            ->where(fn ($q) => $q->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->orWhere(fn ($c) => $c->holdingPurpose(ResponsibilityPurpose::StudentRepCoordination)))->pluck('id');
        $cadences = ResolveCadenceForDuty::forDuties($duties);
        $institutionTypes = self::ancestry(InstitutionType::all());
        $dutyTypes = self::ancestry(DutyType::all());
        $steps = LearningStep::query()->active()->ordered()->with(['audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])->get();
        $knowledge = KnowledgeItem::query()->active()->ordered()
            ->with(['sources.document.institution.tenant', 'section', 'glossaryTerms', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])
            ->withCount(['comments' => fn ($query) => $query->notErased()])->get();
        $progress = LearningProgress::query()->whereIn('user_id', $users->pluck('id'))
            ->whereIn('progressable_type', [MorphMap::alias(LearningStep::class), MorphMap::alias(KnowledgeItem::class)])->get()->groupBy('user_id');
        $detections = [];
        foreach (SystemStepRegistry::definitions() as $key => $definition) {
            if ($definition['detector'] !== null) {
                $detections[$key] = app($definition['detector'])->doneFor($users->pluck('id')->values())->flip();
            }
        }
        $paths = collect();
        foreach ($users as $user) {
            $userDuties = $user->authorization_duties;
            $repDuties = $userDuties->whereIn('id', $repIds)->sortBy(fn (Duty $duty) => $duty->pivot->start_date);
            $first = $repDuties->first();
            $termStart = $first?->pivot->start_date;
            $end = $first?->pivot->end_date ?? ($first ? ($cadences[$first->id] ?? null)?->end_date : null);
            $facts = [];
            foreach ($userDuties as $duty) {
                $tenant = $duty->institution?->tenant_id;
                $values = [
                    'everyone' => ['*'],
                    'duty' => [$duty->id],
                    'institution' => $duty->institution ? [$duty->institution->id] : [],
                    'institution_type' => $duty->institution?->types->flatMap(fn ($type) => $institutionTypes[$type->id] ?? [$type->id])->all() ?? [],
                    'duty_type' => $duty->types->flatMap(fn ($type) => $dutyTypes[$type->id] ?? [$type->id])->all(),
                    'responsibility' => $duty->roles->pluck('id')->all(),
                    'purpose' => $duty->roles->pluck('purpose')->filter()->map(fn (ResponsibilityPurpose $purpose) => $purpose->value)->all(),
                ];
                foreach ($values as $type => $ids) {
                    foreach ($ids as $id) {
                        $facts[$type][(string) $id]['all'] = true;
                        if ($tenant !== null) {
                            $facts[$type][(string) $id][$tenant] = true;
                        }
                    }
                }
            }
            $userProgress = $progress->get($user->id, collect())->keyBy(fn (LearningProgress $row): string => $row->progressable_type.':'.$row->progressable_id);
            $resolved = collect();
            $meetingPrep = collect();
            $knowledgeItems = collect();
            foreach ($steps->concat($knowledge) as $step) {
                $matched = $step->audiences->filter(fn (LearningAudience $audience): bool => self::inAudience($audience, $facts));
                if ($matched->isEmpty()) {
                    continue;
                }
                $winner = $matched->sortBy(fn (LearningAudience $audience): array => [
                    array_search($audience->importance, LearningImportance::cases(), true),
                    $audience->stage?->order() ?? 4, $audience->order, $audience->id,
                ])->first();
                $locale = app()->getLocale();
                $notes = $matched->filter(fn (LearningAudience $audience): bool => filled($audience->getTranslation('description', $locale, false)))
                    ->map(fn (LearningAudience $audience): array => ['label' => $audience->label($locale), 'description' => (string) $audience->getTranslation('description', $locale, false)])->values()->all();
                $item = self::resolveStep($step, $winner, $notes, $userProgress->get($step->getMorphClass().':'.$step->id), $termStart,
                    $step instanceof LearningStep && $step->is_system && isset($detections[$step->key][$user->id]));
                if ($step instanceof KnowledgeItem) {
                    $knowledgeItems->push($item);

                    continue;
                }
                match ($step->kind) {
                    LearningStepKind::Action => $resolved->push($item),
                    LearningStepKind::MeetingPrep => $meetingPrep->push($item),
                };
            }
            $sort = fn (ResolvedLearningItem $item): array => [$item->stage?->order() ?? 4, $item->item->order, $item->item->id];
            $paths->put($user->id, new LearningPath($user, self::stage($first !== null, $termStart, $end), $termStart,
                $resolved->sortBy($sort)->values(), $knowledgeItems->sortBy($sort)->values(), $meetingPrep->sortBy($sort)->values()));
        }

        return $paths;
    }

    private static function inAudience(LearningAudience $audience, array $facts): bool
    {
        if ($audience->type === LearningAudienceType::Everyone && $audience->tenant_id === null) {
            return true;
        }
        $source = $audience->type === LearningAudienceType::Everyone ? '*' : $audience->source_id;

        return isset($facts[$audience->type->value][$source][$audience->tenant_id ?? 'all']);
    }

    /** Incoming reps prepare before their term starts, so per-term work done in the lead-up still counts. */
    private const int TERM_LEAD_IN_DAYS = 90;

    private static function completionIsCurrent(LearningStep|KnowledgeItem $step, ?Carbon $date, ?Carbon $termStart): bool
    {
        return $date !== null && (! $step->per_term || $termStart === null || $date->gte($termStart->copy()->subDays(self::TERM_LEAD_IN_DAYS)));
    }

    private static function resolveStep(LearningStep|KnowledgeItem $step, LearningAudience $winner, array $notes, ?LearningProgress $progress, ?Carbon $termStart, bool $detected): ResolvedLearningItem
    {
        $stage = $step instanceof LearningStep && $step->kind === LearningStepKind::Action ? $winner->stage : null;
        $completedAt = self::completionIsCurrent($step, $progress?->completed_at, $termStart) ? $progress->completed_at : null;
        $state = 'pending';
        if ($completedAt !== null) {
            $state = $progress->status === LearningProgressStatus::Done ? 'done' : ($winner->importance->isSkippable() ? 'skipped' : 'pending');
        }
        if ($detected) {
            return new ResolvedLearningItem($step, 'done', true, $completedAt, $winner->importance, $stage, $winner->label(app()->getLocale()), $notes, $winner->id, $winner->type->value.':'.$winner->source_id.':'.$winner->tenant_id);
        }
        if (self::sourceUpdated($step, $state, $completedAt)) {
            $state = 'updated';
        }

        return new ResolvedLearningItem($step, $state, false, $completedAt, $winner->importance, $stage, $winner->label(app()->getLocale()), $notes, $winner->id, $winner->type->value.':'.$winner->source_id.':'.$winner->tenant_id);
    }

    private static function sourceUpdated(LearningStep|KnowledgeItem $step, string $state, ?Carbon $completedAt): bool
    {
        if (! $step instanceof KnowledgeItem) {
            return false;
        }
        $modified = $step->sourceFor(app()->getLocale())?->document?->sharepoint_modified_at;

        return $state === 'done' && $completedAt !== null && $modified !== null && $modified->gt($completedAt);
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
