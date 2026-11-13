<?php

namespace App\Services;

use App\Actions\ResolveLearningPath;
use App\Enums\InstitutionActivityStatus;
use App\Enums\ResponsibilityPurpose;
use App\Models\Activity;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use App\Support\MorphMap;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ViSAK → Atstovai: who reps are, who needs a coordinator, and how they are doing.
 *
 * @phpstan-type RepInstitution array{id: string, name: string, tenant: string|null, status: InstitutionActivityStatus, lastMeetingAt: string|null, nextMeetingAt: string|null}
 * @phpstan-type RepLearning array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}
 * @phpstan-type RepAttention array{reason: string, institution: string|null}
 * @phpstan-type RepRow array{id: string, name: string, email: string, photo: string|null, institutions: list<RepInstitution>, termStart: string|null, incoming: bool, lastAction: string|null, activity: string, meetings: array{recorded: int, held: int}, learning: RepLearning|null, attention: RepAttention|null}
 * @phpstan-type RepSummary array{total: int, incoming: int, attention: int, activity: array{week: int, month: int, stale: int, never: int}, recordedOwn: int, learning: array{reps: int, finished: int, inProgress: int, notStarted: int}|null}
 */
class RepresentativesPresenter
{
    /** Meetings a rep recorded themselves are counted over this many days. */
    public const int MEETING_WINDOW_DAYS = 90;

    /** Essentials untouched this long into a term become a reason to reach out. */
    public const int ESSENTIALS_GRACE_DAYS = 14;

    /** Ordered by how much each needs a coordinator. */
    public const array ATTENTION_REASONS = ['never_logged_in', 'institution_overdue', 'essentials_not_started', 'inactive'];

    public static function canView(User $viewer): bool
    {
        return $viewer->isSuperAdmin()
            || self::visibleTenantIds($viewer)->isNotEmpty()
            || self::coordinatedInstitutionIds($viewer)->isNotEmpty();
    }

    public static function canViewRep(User $viewer, User $rep): bool
    {
        if ($viewer->isSuperAdmin()) {
            return true;
        }

        $institutionIds = self::visibleTenantIds($viewer)->isEmpty()
            ? collect()
            : RepStatisticsScope::institutions(self::visibleTenantIds($viewer)->all())->pluck('id');

        return self::terms($institutionIds->merge(self::coordinatedInstitutionIds($viewer))->unique())
            ->where('dutiable_id', $rep->id)->exists();
    }

    /** @return Collection<int, int> */
    public static function visibleTenantIds(User $viewer): Collection
    {
        return once(fn () => app(AtstovavimasSettings::class)->getVisibleTenantIds($viewer)->map(fn ($id): int => (int) $id)->values());
    }

    /**
     * Learning progress follows coordination only (`.ai/rules/learning.md`), never padalinys rights.
     *
     * @return Collection<int, string>
     */
    public static function coordinatedInstitutionIds(User $viewer): Collection
    {
        return once(fn () => app(ResponsibilityResolver::class)
            ->institutionIdsFor($viewer, ResponsibilityPurpose::StudentRepCoordination)
            ->map(fn ($id): string => (string) $id)->values());
    }

    /**
     * @param  list<int>  $tenantIds  already narrowed to the viewer's visible padaliniai
     * @return array{reps: list<RepRow>, summary: RepSummary}
     */
    public static function overview(User $viewer, array $tenantIds, string $locale): array
    {
        $institutionIds = ($tenantIds === [] ? collect() : RepStatisticsScope::institutions($tenantIds)->pluck('id'))
            ->map(fn ($id): string => (string) $id)
            ->merge($viewer->isSuperAdmin() ? [] : self::coordinatedInstitutionIds($viewer))
            ->unique()->values();
        $reps = self::rows($viewer, self::terms($institutionIds)->get(['id', 'duty_id', 'dutiable_id', 'start_date', 'end_date']), $locale);

        return ['reps' => $reps, 'summary' => self::summary($reps)];
    }

    /**
     * One rep, counted over every institution they represent in.
     *
     * @return RepRow|null
     */
    public static function representative(User $viewer, User $rep, string $locale): ?array
    {
        $terms = Dutiable::query()->current()->where('dutiable_type', MorphMap::alias(User::class))->where('dutiable_id', $rep->id)
            ->whereHas('duty', fn ($duties) => $duties->where('is_active', true)->holdingPurpose(ResponsibilityPurpose::StudentRepresentation))
            ->with('duty:id,institution_id')
            ->get(['id', 'duty_id', 'dutiable_id', 'start_date', 'end_date']);

        return self::rows($viewer, $terms, $locale)[0] ?? null;
    }

    public static function seesLearningOf(User $viewer, User $rep): bool
    {
        if ($viewer->isSuperAdmin()) {
            return true;
        }

        return self::terms(self::coordinatedInstitutionIds($viewer))->where('dutiable_id', $rep->id)->exists();
    }

    /**
     * Current and upcoming terms on active representation duties in the given institutions.
     *
     * @param  Collection<int, mixed>  $institutionIds
     * @return Builder<Dutiable>
     */
    private static function terms(Collection $institutionIds): Builder
    {
        return Dutiable::query()->current()->where('dutiable_type', MorphMap::alias(User::class))
            ->whereHas('duty', fn ($duties) => $duties->where('is_active', true)
                ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->whereIn('institution_id', $institutionIds->all()))
            ->with('duty:id,institution_id');
    }

    /**
     * @param  Collection<int, Dutiable>  $terms
     * @return list<RepRow>
     */
    private static function rows(User $viewer, Collection $terms, string $locale): array
    {
        $users = User::query()->whereIn('id', $terms->pluck('dutiable_id')->unique()->values())
            ->get(['id', 'name', 'email', 'profile_photo_path', 'last_action']);
        if ($users->isEmpty()) {
            return [];
        }

        $institutionIds = $terms->pluck('duty.institution_id')->unique()->values();
        $institutions = app(InstitutionActivityStatusService::class)->withSummary(Institution::query()->whereIn('institutions.id', $institutionIds))
            ->with('tenant:id,shortname')->get()->keyBy(fn (Institution $institution): string => (string) $institution->id);
        $statuses = $institutions->map(fn (Institution $institution) => app(InstitutionActivityStatusService::class)->resolve($institution));

        $coordinated = $viewer->isSuperAdmin() ? null : self::coordinatedInstitutionIds($viewer)->flip();
        $learningUsers = $users->filter(fn (User $user): bool => $coordinated === null
            || $terms->where('dutiable_id', $user->id)->contains(fn (Dutiable $term): bool => $coordinated->has((string) $term->duty->institution_id)));
        $paths = ResolveLearningPath::forUsers($learningUsers->values());
        $meetings = self::meetingCounts($terms, $institutionIds);
        $now = now();

        $rows = [];
        foreach ($users as $user) {
            $own = $terms->where('dutiable_id', $user->id);
            $termStart = $own->min('start_date');
            $incoming = $own->isNotEmpty() && $own->every(fn (Dutiable $term): bool => $term->start_date->isAfter(today()));
            $repInstitutions = [];
            foreach ($own->pluck('duty.institution_id')->unique() as $institutionId) {
                $institution = $institutions->get((string) $institutionId);
                if ($institution === null) {
                    continue;
                }
                $tenant = $institution->tenant?->shortname;
                $repInstitutions[] = [
                    'id' => (string) $institution->id,
                    'name' => (string) $institution->getTranslation('name', $locale),
                    'tenant' => $tenant === null ? null : (string) __($tenant),
                    'status' => $statuses[(string) $institution->id]->status,
                    'lastMeetingAt' => $statuses[(string) $institution->id]->lastMeetingAt?->toISOString(),
                    'nextMeetingAt' => $statuses[(string) $institution->id]->nextMeetingAt?->toISOString(),
                ];
            }
            $path = $paths->get($user->id);
            $learning = $path?->totals();
            $user->makeVisible('last_action');
            $row = [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->profile_photo_path,
                'institutions' => $repInstitutions,
                'termStart' => $termStart?->toDateString(),
                'incoming' => $incoming,
                'lastAction' => $user->last_action?->toISOString(),
                'activity' => self::activity($user->last_action, $now),
                'meetings' => $meetings[$user->id] ?? ['recorded' => 0, 'held' => 0],
                'learning' => $learning,
                'attention' => null,
            ];
            $row['attention'] = self::attention($row, $termStart);
            $rows[] = $row;
        }

        $collator = new \Collator($locale);
        usort($rows, function (array $a, array $b) use ($collator): int {
            $rank = fn (array $row): int => $row['attention'] === null ? count(self::ATTENTION_REASONS) : (int) array_search($row['attention']['reason'], self::ATTENTION_REASONS, true);

            return $rank($a) <=> $rank($b) ?: (int) $collator->compare($a['name'], $b['name']);
        });

        return $rows;
    }

    private static function activity(?CarbonInterface $lastAction, CarbonInterface $now): string
    {
        return match (true) {
            $lastAction === null => 'never',
            $lastAction->gte($now->copy()->subDays(7)) => 'week',
            $lastAction->gte($now->copy()->subDays(30)) => 'month',
            default => 'stale',
        };
    }

    /**
     * The one reason a coordinator should reach out first; incoming reps have nothing to answer for yet.
     *
     * @param  RepRow  $row
     * @return RepAttention|null
     */
    private static function attention(array $row, ?CarbonInterface $termStart): ?array
    {
        if ($row['incoming']) {
            return null;
        }
        if ($row['activity'] === 'never') {
            return ['reason' => 'never_logged_in', 'institution' => null];
        }
        foreach ($row['institutions'] as $institution) {
            if ($institution['status'] === InstitutionActivityStatus::Overdue) {
                return ['reason' => 'institution_overdue', 'institution' => $institution['name']];
            }
        }
        $learning = $row['learning'];
        if ($learning !== null && $learning['essentialTotal'] > 0 && $learning['essentialDone'] === 0
            && $termStart !== null && $termStart->lte(today()->subDays(self::ESSENTIALS_GRACE_DAYS))) {
            return ['reason' => 'essentials_not_started', 'institution' => null];
        }
        if ($row['activity'] === 'stale') {
            return ['reason' => 'inactive', 'institution' => null];
        }

        return null;
    }

    /**
     * Meetings held in each rep's institutions over the window, and how many of them the rep recorded.
     *
     * @param  Collection<int, Dutiable>  $terms
     * @param  Collection<int, mixed>  $institutionIds
     * @return array<string, array{recorded: int, held: int}>
     */
    private static function meetingCounts(Collection $terms, Collection $institutionIds): array
    {
        $held = DB::table('institution_meeting')
            ->join('meetings', 'meetings.id', '=', 'institution_meeting.meeting_id')
            ->whereNull('meetings.deleted_at')
            ->whereIn('institution_meeting.institution_id', $institutionIds->all())
            ->whereBetween('meetings.start_time', [now()->subDays(self::MEETING_WINDOW_DAYS), now()])
            ->get(['institution_meeting.institution_id', 'institution_meeting.meeting_id'])
            ->groupBy(fn (object $row): string => (string) $row->institution_id)
            ->map(fn (Collection $rows): array => $rows->pluck('meeting_id')->map(fn ($id): string => (string) $id)->all());
        $meetingIds = $held->flatten()->unique()->values();
        $creators = $meetingIds->isEmpty() ? collect() : Activity::query()
            ->where('subject_type', MorphMap::alias(Meeting::class))->where('event', 'created')
            ->where('causer_type', MorphMap::alias(User::class))
            ->whereIn('subject_id', $meetingIds->all())
            ->pluck('causer_id', 'subject_id')->map(fn ($id): string => (string) $id);

        $counts = [];
        foreach ($terms->groupBy('dutiable_id') as $userId => $own) {
            $ids = $own->flatMap(fn (Dutiable $term): array => $held->get((string) $term->duty->institution_id, []))->unique();
            $counts[(string) $userId] = [
                'recorded' => $ids->filter(fn (string $id): bool => $creators->get($id) === (string) $userId)->count(),
                'held' => $ids->count(),
            ];
        }

        return $counts;
    }

    /**
     * @param  list<RepRow>  $reps
     * @return RepSummary
     */
    private static function summary(array $reps): array
    {
        $summary = ['total' => count($reps), 'incoming' => 0, 'attention' => 0, 'activity' => ['week' => 0, 'month' => 0, 'stale' => 0, 'never' => 0], 'recordedOwn' => 0, 'learning' => null];
        $learning = ['reps' => 0, 'finished' => 0, 'inProgress' => 0, 'notStarted' => 0];
        foreach ($reps as $rep) {
            if ($rep['incoming']) {
                $summary['incoming']++;

                continue;
            }
            $summary['activity'][$rep['activity']]++;
            $summary['attention'] += (int) ($rep['attention'] !== null);
            $summary['recordedOwn'] += (int) ($rep['meetings']['recorded'] > 0);
            if ($rep['learning'] !== null && $rep['learning']['essentialTotal'] > 0) {
                $learning['reps']++;
                $done = $rep['learning']['essentialDone'];
                $key = match (true) {
                    $done === 0 => 'notStarted',
                    $done >= $rep['learning']['essentialTotal'] => 'finished',
                    default => 'inProgress',
                };
                $learning[$key]++;
            }
        }
        $summary['learning'] = $learning['reps'] > 0 ? $learning : null;

        return $summary;
    }
}
