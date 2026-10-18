<?php

namespace App\Actions\ActivityRequests;

use App\Models\InstitutionActivityRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Open activity requests as one span per institution and campaign, for the meetings Gantt. Visibility
 * follows GetInstitutionActivityRequestHistory: askers see every request, anyone else only their own.
 */
class GetOpenActivityRequestSpans
{
    public function __construct(private readonly ResolveAskableInstitutions $askable) {}

    /**
     * @param  Collection<int, string>  $institutionIds
     * @return list<array{institution_id: string, campaign: string, period_start: string, period_end: string, sent_at: string|null, expires_at: string, recipients_count: int}>
     */
    public function execute(User $user, Collection $institutionIds): array
    {
        if ($institutionIds->isEmpty()) {
            return [];
        }

        $askableIds = $this->askable->query($user)->whereIn('id', $institutionIds)->pluck('id');

        return InstitutionActivityRequest::query()
            ->open()
            ->whereIn('institution_id', $institutionIds)
            ->where(fn (Builder $query) => $query
                ->whereIn('institution_id', $askableIds)
                ->orWhere('recipient_id', $user->id))
            ->get(['id', 'institution_id', 'recipient_id', 'campaign_type', 'period_start', 'period_end', 'created_at', 'expires_at'])
            ->groupBy(fn (InstitutionActivityRequest $request) => $request->institution_id.'|'.$request->campaign_type->value)
            ->map(function (Collection $requests): array {
                /** @var InstitutionActivityRequest $first */
                $first = $requests->first();

                return [
                    'institution_id' => (string) $first->institution_id,
                    'campaign' => $first->campaign_type->value,
                    'period_start' => $requests->min(fn (InstitutionActivityRequest $request) => $request->period_start)->toDateString(),
                    'period_end' => $requests->max(fn (InstitutionActivityRequest $request) => $request->periodEnd())->toDateString(),
                    'sent_at' => $requests->min(fn (InstitutionActivityRequest $request) => $request->created_at)?->toISOString(),
                    'expires_at' => $requests->max(fn (InstitutionActivityRequest $request) => $request->expires_at)->toISOString(),
                    'recipients_count' => $requests->pluck('recipient_id')->unique()->count(),
                ];
            })
            ->values()
            ->all();
    }
}
