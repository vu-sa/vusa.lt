<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\MeetingType;
use App\Http\Controllers\Api\ApiController;
use App\Models\Institution;
use App\Models\Meeting;
use App\Services\InstitutionActivityStatusService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ResourceServices\DutyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Feeds the guided action window ("Veiksmų langas") the personalised context its
 * choice screens read: which of the caller's institutions need a meeting, and
 * which of their meetings are missing something.
 *
 * Scope comes entirely from the caller's own duties and permissions, so there is
 * nothing to authorize beyond being logged in — a user can only ever see their own
 * bodies, and what they may create for.
 */
class ActionWindowApiController extends ApiController
{
    /**
     * Ranks the "papildyti posėdį" list: a meeting with no agenda items at all is
     * further from done than one whose votes are merely unfinished.
     */
    private const array COMPLETION_PRIORITY = ['no_items' => 0, 'incomplete' => 1];

    /** How far back to look when guessing when a body meets. */
    private const int PATTERN_SAMPLE = 8;

    /**
     * @route GET /api/v1/admin/action-window/context
     *
     * @routeName api.v1.admin.actionWindow.context
     */
    public function context(InstitutionActivityStatusService $activityStatus, Authorizer $authorizer): JsonResponse
    {
        $institutions = DutyService::getUserInstitutionsForDashboard();

        return $this->jsonSuccess([
            'institutions' => $this->institutionPayload($institutions, $activityStatus),
            'meetingsNeedingAttention' => $this->meetingPayload($institutions),
            'institutionSearch' => $this->institutionSearchScope($authorizer),
        ]);
    }

    /**
     * Whether the window may offer bodies beyond the caller's own duties, and which
     * tenants that search is then scoped to.
     *
     * A caller with only `.own` scope holds no `meetings.create.padalinys`, so
     * GetTenantsForUpserts hands back nothing and the option stays hidden — offering it
     * would only lead to a rejection from StoreMeetingRequest's tenant-scope rule.
     * All-scope callers get an empty list, meaning no tenant filter at all.
     *
     * @return array{enabled: bool, tenant_ids: array<int, int>}
     */
    private function institutionSearchScope(Authorizer $authorizer): array
    {
        $tenants = GetTenantsForUpserts::execute('meetings.create.padalinys', $authorizer);

        return [
            'enabled' => $authorizer->isAllScope || $tenants->isNotEmpty(),
            'tenant_ids' => $authorizer->isAllScope
                ? []
                : $tenants->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
        ];
    }

    /**
     * Translatable attributes are annotated `array|string|null` across the app, so
     * narrow to the resolved string the JSON contract promises.
     */
    private function localized(array|string|null $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     * @return array<int, array<string, mixed>>
     */
    private function institutionPayload(Collection $institutions, InstitutionActivityStatusService $activityStatus): array
    {
        return $institutions
            ->map(fn (Institution $institution) => [
                'id' => (string) $institution->id,
                'name' => $this->localized($institution->name),
                'tenant_shortname' => $institution->tenant?->shortname,
                // Decides whether the window may offer to announce the meeting publicly.
                'is_internal' => $institution->governance_scope->isInternal(),
                'meeting_pattern' => $this->meetingPattern($institution),
                'activity_status' => $activityStatus->resolve($institution)->toArray(),
            ])
            ->sortBy([
                fn (array $a, array $b) => ($b['activity_status']['requires_action'] ?? false) <=> ($a['activity_status']['requires_action'] ?? false),
                fn (array $a, array $b) => ($b['activity_status']['priority'] ?? 0) <=> ($a['activity_status']['priority'] ?? 0),
                fn (array $a, array $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * When this body usually meets, from its own history.
     *
     * A generic "tomorrow at 18:00" is wrong for almost every institution, so the window
     * only ever suggests a date it can justify: the weekday these meetings actually fall
     * on, at the time the most recent one on that weekday started.
     *
     * Email meetings are excluded — their `start_time` is a 23:59 deadline marker rather
     * than an hour anyone met at.
     *
     * @return array{weekday: int, time: string}|null
     */
    private function meetingPattern(Institution $institution): ?array
    {
        $past = $institution->meetings
            ->filter(fn (Meeting $meeting) => $meeting->type !== MeetingType::Email && $meeting->start_time->isPast())
            ->sortByDesc('start_time')
            ->take(self::PATTERN_SAMPLE);

        if ($past->isEmpty()) {
            return null;
        }

        $weekday = $past
            ->countBy(fn (Meeting $meeting) => $meeting->start_time->dayOfWeekIso)
            ->sortDesc()
            ->keys()
            ->first();

        $onThatWeekday = $past->first(
            fn (Meeting $meeting) => $meeting->start_time->dayOfWeekIso === (int) $weekday
        );

        return [
            'weekday' => (int) $weekday,
            'time' => $onThatWeekday->start_time->format('H:i'),
        ];
    }

    /**
     * Meetings the caller could still fill in, most-incomplete first. Limited to
     * meetings that have already started — there is nothing to record about one
     * that has not happened yet.
     *
     * @param  Collection<int, Institution>  $institutions
     * @return array<int, array<string, mixed>>
     */
    private function meetingPayload(Collection $institutions): array
    {
        return $institutions
            ->flatMap(fn (Institution $institution) => $institution->meetings
                ->filter(fn (Meeting $meeting) => $meeting->start_time->isPast())
                ->map(fn (Meeting $meeting) => [
                    'id' => (string) $meeting->id,
                    'title' => $meeting->title,
                    'start_time' => $meeting->start_time->toIso8601String(),
                    'institution_id' => (string) $institution->id,
                    'institution_name' => $this->localized($institution->name),
                    'completion_status' => $meeting->completion_status,
                ])
                ->all())
            ->filter(fn (array $meeting) => isset(self::COMPLETION_PRIORITY[$meeting['completion_status']]))
            ->unique('id')
            ->sortBy([
                fn (array $a, array $b) => self::COMPLETION_PRIORITY[$a['completion_status']] <=> self::COMPLETION_PRIORITY[$b['completion_status']],
                fn (array $a, array $b) => $b['start_time'] <=> $a['start_time'],
            ])
            ->take(15)
            ->values()
            ->all();
    }
}
