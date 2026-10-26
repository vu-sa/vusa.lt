<?php

namespace App\Actions\ActivityRequests;

use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\Support\ActivityRequests\ActivityRequestHistoryMapper;
use Illuminate\Database\Eloquent\Builder;

class GetInstitutionActivityRequestHistory
{
    public function __construct(private readonly ActivityRequestHistoryMapper $mapper) {}

    /** @return Builder<InstitutionActivityRequest> */
    private function query(Institution $institution, User $user): Builder
    {
        return InstitutionActivityRequest::query()->where('institution_id', $institution->id)
            ->when(! $user->can('askAboutActivity', [InstitutionCheckIn::class, $institution]), fn ($query) => $query->where('recipient_id', $user->id));
    }

    public function visible(Institution $institution, User $user): bool
    {
        return $user->can('askAboutActivity', [InstitutionCheckIn::class, $institution]) || $this->query($institution, $user)->exists();
    }

    public function execute(Institution $institution, User $user, int $page = 1): array
    {
        $batches = $this->query($institution, $user)->select('send_id')->selectRaw('MAX(created_at) AS sent_at')
            ->groupBy('send_id')->orderByDesc('sent_at')->orderByDesc('send_id')->paginate(20, page: max(1, $page));
        $requests = $this->query($institution, $user)->whereIn('send_id', $batches->getCollection()->pluck('send_id'))
            ->with(ActivityRequestHistoryMapper::RELATIONS)->get()->groupBy('send_id');

        return ['data' => $batches->getCollection()->map(fn ($batch) => [
            'id' => $batch->send_id,
            'campaigns' => $requests->get($batch->send_id, collect())->groupBy('campaign_type')->map(fn ($campaignRequests) => $campaignRequests->map(fn (InstitutionActivityRequest $request) => $this->mapper->map($request, $user))->values()->all())->all(),
        ])->values()->all(), 'next_page' => $batches->hasMorePages() ? $batches->currentPage() + 1 : null];
    }
}
