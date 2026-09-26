<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SerializeReservationCart;
use App\Actions\SerializeReservationsForTable;
use App\Http\Controllers\AdminController;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReservationsDashboardController extends AdminController
{
    /** Item states that still need someone: neither returned, rejected nor cancelled. */
    private const array IN_FLIGHT = ['created', 'reserved', 'lent'];

    private const int LIST_SIZE = 5;

    public function __construct(
        public Authorizer $authorizer,
    ) {}

    public function reservations()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);

        $managedTenantIds = $this->authorizer
            ->tenants($user, config('permission.resource_managership_indicating_permission'))
            ->pluck('id');

        // Counts are aggregates and stay on the first paint; the two lists are the secondary group.
        return $this->inertiaResponse('Admin/Dashboard/ShowReservations', [
            'managesResources' => $managedTenantIds->isNotEmpty(),
            'reservationCart' => SerializeReservationCart::execute($user),
            'counts' => [
                'waitingForMe' => $this->administered($managedTenantIds, ['created'])->count(),
                'lentOut' => $this->administered($managedTenantIds, ['lent'])->count(),
                'overdue' => $this->administered($managedTenantIds, self::IN_FLIGHT, overdue: true)->count(),
                'mine' => $this->mine($user, self::IN_FLIGHT)->count(),
                'myOverdue' => $this->mine($user, self::IN_FLIGHT, overdue: true)->count(),
            ],
            'waitingForMe' => Inertia::defer(fn (): array => $this->serialize(
                $this->administered($managedTenantIds, ['created'])->orderBy('start_time'),
                $user,
            ), 'secondary'),
            'myUpcoming' => Inertia::defer(fn (): array => $this->serialize(
                $this->mine($user, self::IN_FLIGHT)->orderBy('start_time'),
                $user,
            ), 'secondary'),
        ]);
    }

    /**
     * Reservations holding an item, in one of the states, that belongs to a tenant the user manages.
     *
     * @param  Collection<int, int|string>  $managedTenantIds
     * @param  list<string>  $states
     * @return Builder<Reservation>
     */
    private function administered(Collection $managedTenantIds, array $states, bool $overdue = false): Builder
    {
        return Reservation::query()->whereHas('resources', function ($resources) use ($managedTenantIds, $states, $overdue): void {
            $resources->whereIn('resources.tenant_id', $managedTenantIds)
                ->whereIn('reservation_resource.state', $states);

            if ($overdue) {
                $resources->where('reservation_resource.end_time', '<', now());
            }
        });
    }

    /**
     * @param  list<string>  $states
     * @return Builder<Reservation>
     */
    private function mine(User $user, array $states, bool $overdue = false): Builder
    {
        return Reservation::query()
            ->whereHas('users', fn ($users) => $users->where('users.id', $user->id))
            ->whereHas('resources', function ($resources) use ($states, $overdue): void {
                $resources->whereIn('reservation_resource.state', $states);

                if ($overdue) {
                    $resources->where('reservation_resource.end_time', '<', now());
                }
            });
    }

    /**
     * @param  Builder<Reservation>  $query
     * @return list<array<string, mixed>>
     */
    private function serialize(Builder $query, User $user): array
    {
        return SerializeReservationsForTable::execute(
            $query->with(SerializeReservationsForTable::EAGER_LOADS)->take(self::LIST_SIZE)->get(),
            $user,
            $this->authorizer,
        );
    }
}
