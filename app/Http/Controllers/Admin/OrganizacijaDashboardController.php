<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetRecentlyEditedRecords;
use App\Http\Controllers\AdminController;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;

/**
 * Organizacija's overview (PR 7.3): who holds which seat, and which seats need a person.
 *
 * Everything is scoped through `duties.read.padalinys`, the permission the duties list itself
 * uses, so a number never counts a seat its link would not show.
 */
class OrganizacijaDashboardController extends AdminController
{
    /** A term ending within this many days needs a successor. */
    private const int ENDING_WITHIN_DAYS = 30;

    private const int LIST_SIZE = 5;

    public function __construct(public Authorizer $authorizer) {}

    public function index()
    {
        $this->authorizeWorkspace('organizacija');

        $user = User::query()->find(request()->user()?->id) ?? abort(404);

        $tenantIds = $this->authorizer->tenants($user, 'duties.read.padalinys')->pluck('id');

        $canSeeDuties = $user->can('viewAny', Duty::class);
        $canSeeUsers = $user->can('viewAny', User::class);

        $ending = $this->endingTerms($tenantIds);

        return $this->inertiaResponse('Admin/Dashboard/ShowOrganizacija', [
            'counts' => [
                'endingSoon' => $canSeeDuties ? (clone $ending)->count() : null,
                'emptyDuties' => $canSeeDuties ? $this->duties($tenantIds)->whereDoesntHave('current_users')->count() : null,
                'duties' => $canSeeDuties ? $this->duties($tenantIds)->count() : null,
                'members' => $canSeeUsers ? $this->members($tenantIds)->count() : null,
            ],
            'endingTerms' => $canSeeDuties ? $this->serializeEnding($ending) : [],
            'recentlyEdited' => Inertia::defer(
                fn (): array => GetRecentlyEditedRecords::execute($user, 5, ['duty', 'user', 'form'])->all(),
                'secondary',
            ),
        ]);
    }

    /**
     * @param  Collection<int, int|string>  $tenantIds
     * @return Builder<Duty>
     */
    private function duties(Collection $tenantIds): Builder
    {
        return Duty::query()->whereHas('institution', fn ($institution) => $institution->whereIn('tenant_id', $tenantIds));
    }

    /**
     * @param  Collection<int, int|string>  $tenantIds
     * @return Builder<User>
     */
    private function members(Collection $tenantIds): Builder
    {
        return User::query()->whereHas('current_duties.institution', fn ($institution) => $institution->whereIn('tenant_id', $tenantIds));
    }

    /**
     * Terms that end soon and are not already replaced by a later one for the same person and seat.
     *
     * @param  Collection<int, int|string>  $tenantIds
     * @return Builder<Dutiable>
     */
    private function endingTerms(Collection $tenantIds): Builder
    {
        return Dutiable::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(self::ENDING_WITHIN_DAYS)->toDateString()])
            ->whereHas('duty.institution', fn ($institution) => $institution->whereIn('tenant_id', $tenantIds));
    }

    /**
     * @param  Builder<Dutiable>  $ending
     * @return list<array{id: string, duty_id: string, duty: string, user: string|null, ends_on: string|null}>
     */
    private function serializeEnding(Builder $ending): array
    {
        $terms = $ending
            ->with(['duty:id,name', 'user:id,name'])
            ->orderBy('end_date')
            ->take(self::LIST_SIZE)
            ->get();

        $rows = [];

        foreach ($terms as $dutiable) {
            $rows[] = [
                'id' => (string) $dutiable->id,
                'duty_id' => (string) $dutiable->duty?->id,
                'duty' => (string) $dutiable->duty?->name,
                'user' => $dutiable->user?->name,
                'ends_on' => $dutiable->end_date?->toDateString(),
            ];
        }

        return $rows;
    }
}
