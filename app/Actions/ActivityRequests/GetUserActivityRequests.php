<?php

namespace App\Actions\ActivityRequests;

use App\Models\InstitutionActivityRequest;
use App\Models\User;
use App\Support\ActivityRequests\ActivityRequestHistoryMapper;
use Illuminate\Database\Eloquent\Builder;

class GetUserActivityRequests
{
    public function __construct(
        private readonly ResolveAskableInstitutions $askable,
        private readonly ActivityRequestHistoryMapper $mapper,
    ) {}

    /** @return Builder<InstitutionActivityRequest> */
    private function query(User $recipient, User $viewer): Builder
    {
        return InstitutionActivityRequest::query()->where('recipient_id', $recipient->id)
            ->when(! $viewer->is($recipient), fn (Builder $query) => $query
                ->whereIn('institution_id', $this->askable->query($viewer)->select('id')));
    }

    public function visible(User $recipient, User $viewer): bool
    {
        return $this->query($recipient, $viewer)->exists();
    }

    public function history(User $recipient, User $viewer, int $page = 1): array
    {
        $requests = $this->query($recipient, $viewer)
            ->with([...ActivityRequestHistoryMapper::RELATIONS, 'institution'])
            ->orderByRaw('CASE WHEN answered_at IS NULL AND resolved_at IS NULL AND expires_at > ? THEN 0 ELSE 1 END', [now()])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(20, page: max(1, $page));

        return [
            'data' => $requests->getCollection()->map(fn (InstitutionActivityRequest $request) => $this->mapper->forRecipientHistory($request, $viewer))->all(),
            'page' => $requests->currentPage(),
            'next_page' => $requests->hasMorePages() ? $requests->currentPage() + 1 : null,
        ];
    }

    public function activeSummary(User $recipient): array
    {
        $query = InstitutionActivityRequest::query()->where('recipient_id', $recipient->id)->open()->whereHas('institution');

        return [
            'total' => (clone $query)->count(),
            'data' => $query->with('institution')->orderBy('expires_at')->orderBy('id')->limit(3)->get()
                ->map(fn (InstitutionActivityRequest $request) => [
                    'id' => $request->id,
                    'institution_name' => $request->institution->name,
                    'campaign_type' => $request->campaign_type->value,
                    'period_start' => $request->period_start->toDateString(),
                    'period_end' => $request->period_end?->toDateString(),
                    'expires_at' => $request->expires_at->toISOString(),
                    'answer_url' => $request->answerUrl(),
                ])->all(),
        ];
    }
}
