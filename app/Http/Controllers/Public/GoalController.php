<?php

namespace App\Http\Controllers\Public;

use App\Helpers\ShortUrlHelper;
use App\Http\Controllers\PublicController;
use App\Models\Goal;
use App\Models\Step;
use App\Support\Experiments\GoalsExperiment;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public pages of the goals pilot: only a pilot padalinys' goals marked public are shown.
 */
class GoalController extends PublicController
{
    public function index(): Response
    {
        abort_unless(GoalsExperiment::enabledForTenant($this->tenant), 404);

        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('publicGoals.index', $this->subdomain);

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: __('goals.public.index_title'),
            description: __('goals.public.index_description'),
        );

        $goals = Goal::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('is_public', true)
            ->with('cadence')
            ->withCount('steps')
            ->get()
            ->sortByDesc(fn (Goal $goal) => $goal->cadence?->start_date)
            ->values()
            ->map(fn (Goal $goal): array => [
                ...$this->summary($goal),
                'steps_count' => $goal->steps_count,
            ]);

        return Inertia::render('Public/Goals/IndexGoals', [
            'goals' => $goals,
        ]);
    }

    public function show(string $subdomain, string $lang, string $goalsString, string $goal): Response
    {
        abort_unless(GoalsExperiment::enabledForTenant($this->tenant), 404);

        $goal = Goal::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('is_public', true)
            ->with(['cadence', 'responsibleDuty'])
            ->findOrFail($goal);

        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('publicGoals.show', $this->subdomain, extraParameters: ['goal' => $goal->id]);

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: (string) $goal->title,
            description: Str::limit(strip_tags((string) ($goal->expected_result ?: $goal->description)), 160),
            modifiedTime: $goal->updated_at,
        );

        return Inertia::render('Public/Goals/ShowGoal', [
            'goal' => [
                ...$this->summary($goal),
                'description' => $goal->description,
                'evaluation' => $goal->status->isClosed() ? $goal->evaluation : null,
                'responsible_duty' => $goal->responsibleDuty?->name,
            ],
            'steps' => $goal->steps()->with('agendaItem.meeting.institutions.tenant', 'document')->get()->map(fn (Step $step): array => [
                'id' => $step->id,
                'title' => $step->title,
                'description' => $step->description,
                'happened_on' => $step->happened_on->toDateString(),
                'url' => $step->url,
                // Only what a visitor could reach anyway: a public meeting page, a shared document.
                'meeting_url' => $this->publicMeetingUrl($step),
                'document' => $step->document?->anonymous_url ? [
                    'title' => $step->document->title,
                    'url' => ShortUrlHelper::documentUrl($step->document->id),
                ] : null,
            ]),
        ]);
    }

    private function publicMeetingUrl(Step $step): ?string
    {
        $meeting = $step->agendaItem?->meeting;
        $tenant = $meeting?->institutions->first()?->tenant;

        if ($meeting === null || $tenant === null || ! $meeting->isPubliclyVisible()) {
            return null;
        }

        return route('publicMeetings.show', ['subdomain' => $tenant->subdomain(), 'meeting' => $meeting->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Goal $goal): array
    {
        return [
            'id' => $goal->id,
            'title' => $goal->title,
            'expected_result' => $goal->expected_result,
            'status' => $goal->status->value,
            'status_label' => $goal->status->label(),
            'cadence' => $goal->cadence?->label,
            'url' => route('publicGoals.show', ['subdomain' => $this->subdomain, 'goal' => $goal->id]),
        ];
    }
}
