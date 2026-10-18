<?php

namespace App\Actions;

use App\Http\Requests\IndexUserRequest;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\UserDuplicateFinder;
use Illuminate\Database\Eloquent\Builder;

/** The member list's base query, shared by the Inertia page and its API. */
final class BuildUserIndexQuery
{
    /**
     * @return Builder<User>
     */
    public static function execute(IndexUserRequest $request, ModelAuthorizer $authorizer, bool $includeDuplicates = true): Builder
    {
        $query = User::query()
            // Institution names feed the row's quick list of where the person served.
            ->with([
                'duties:id,institution_id',
                'duties.institution:id,name,short_name,tenant_id',
                'duties.institution.tenant:id,shortname',
            ])->withCount('duties')
            ->withCount(['duties as current_duties_count' => self::inOffice(...)])
            ->withExists(['duties as is_current' => self::inOffice(...)])
            ->withMax('duties as last_term_end', 'dutiables.end_date');

        if (! $authorizer->allows($request->user(), 'users.read.*')) {
            $query->where(fn (Builder $query) => $query->whereHas('duties')->orWhereHas('roles'));
        }

        if ($includeDuplicates && ! $request->getShowDeleted() && $request->validated('duplicates') === 'possible') {
            $query->whereIn('users.id', app(UserDuplicateFinder::class)->ids());
        }

        // Role-only and duty-less accounts are neither current nor former.
        match ($request->getFilters()['membership'] ?? $request->validated('membership')) {
            'current' => $query->whereHas('duties', fn (Builder $duties) => self::inOffice($duties)),
            'former' => $query->whereHas('duties')->whereDoesntHave('duties', fn (Builder $duties) => self::inOffice($duties)),
            default => null,
        };

        $futureDuty = $request->getFilters()['future_duty'] ?? $request->validated('future_duty');

        return $futureDuty === 'scheduled'
            ? self::withScheduledDuty($query, $request->user(), $authorizer)
            : $query;
    }

    /** @return Builder<User> */
    public static function scheduledFor(User $actor, ModelAuthorizer $authorizer): Builder
    {
        return self::withScheduledDuty(User::query(), $actor, $authorizer);
    }

    /** Same "in office today" window as User::current_duties(). */
    private static function inOffice(Builder $duties): void
    {
        $duties->whereDate('dutiables.start_date', '<=', today())
            ->where(fn (Builder $dates) => $dates->whereNull('dutiables.end_date')
                ->orWhereDate('dutiables.end_date', '>=', today()));
    }

    /** @param Builder<User> $query */
    private static function withScheduledDuty(Builder $query, User $actor, ModelAuthorizer $authorizer): Builder
    {
        $tenantIds = $actor->isSuperAdmin() || $authorizer->allows($actor, 'users.read.*')
            ? null
            : $authorizer->tenants($actor, 'users.read.padalinys')->pluck('id');

        return $query->whereHas('duties', function (Builder $dutyQuery) use ($tenantIds): void {
            $dutyQuery->whereDate('dutiables.start_date', '>', now()->toDateString())
                ->where(fn (Builder $dates) => $dates->whereNull('dutiables.end_date')
                    ->orWhereDate('dutiables.end_date', '>=', today()));

            if ($tenantIds !== null) {
                $dutyQuery->whereHas('institution', fn (Builder $institution) => $institution->whereIn('tenant_id', $tenantIds));
            }
        });
    }
}
