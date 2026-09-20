<?php

namespace App\Actions;

use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * "Mano rolės ir pareigybės" (O14): what the acting user holds, and for how long.
 *
 * Read from the dutiable rows rather than `User::duties()`, whose pivot omits `tenant_id` and
 * `via_dutiable_id` — the two facts that tell an ex-officio seat or a cross-tenant
 * representative apart from an ordinary term. Never takes a subject other than the actor.
 */
class GetUserAccessSummary
{
    /** Ended terms are a memory aid, not an archive. */
    private const int ENDED_LIMIT = 10;

    /** How far back the dated access-change history reaches (U14), and how many events it lists. */
    private const int HISTORY_DAYS = 365;

    private const int HISTORY_LIMIT = 20;

    /**
     * @return array{
     *     isSuperAdmin: bool,
     *     directRoles: list<string>,
     *     current: list<array<string, mixed>>,
     *     upcoming: list<array<string, mixed>>,
     *     ended: list<array<string, mixed>>,
     *     history: list<array<string, mixed>>
     * }
     */
    public static function execute(User $user): array
    {
        $today = now()->toDateString();

        /** @var Collection<int, Dutiable> $terms */
        $terms = $user->dutiables()
            ->with(['duty.institution.tenant', 'duty.roles:id,name', 'tenant:id,shortname'])
            ->orderByDesc('start_date')
            ->get()
            ->filter(fn (Dutiable $term): bool => $term->duty !== null);

        $started = fn (Dutiable $term): bool => $term->start_date->toDateString() <= $today;
        $notEnded = fn (Dutiable $term): bool => $term->end_date === null || $term->end_date->toDateString() >= $today;

        return [
            'isSuperAdmin' => $user->isSuperAdmin(),
            'directRoles' => $user->roles->pluck('name')->values()->all(),
            'current' => self::present($user, $terms->filter(fn (Dutiable $term): bool => $started($term) && $notEnded($term))),
            'upcoming' => self::present($user, $terms->reject($started)),
            'ended' => self::present($user, $terms->reject($notEnded)->take(self::ENDED_LIMIT)),
            'history' => GetRecentAccessChanges::execute($user, self::HISTORY_DAYS, limit: self::HISTORY_LIMIT),
        ];
    }

    /**
     * @param  Collection<int, Dutiable>  $terms
     * @return list<array<string, mixed>>
     */
    private static function present(User $user, Collection $terms): array
    {
        return $terms->map(function (Dutiable $term) use ($user): array {
            $duty = $term->duty;
            $institution = $duty->institution;

            // Only offer a link the user may follow — a 403 from your own roles page would be absurd.
            return [
                'id' => $term->id,
                'dutyName' => $duty->name,
                'dutyHref' => $user->can('view', $duty) ? route('duties.show', $duty) : null,
                'institutionName' => $institution?->name,
                'institutionHref' => $institution !== null && $user->can('view', $institution)
                    ? route('institutions.show', $institution)
                    : null,
                'tenant' => $institution?->tenant?->shortname,
                'representsTenant' => $term->tenant_id !== null && $term->tenant_id !== $institution?->tenant_id
                    ? $term->tenant?->shortname
                    : null,
                'startDate' => $term->start_date->toDateString(),
                'endDate' => $term->end_date?->toDateString(),
                'isExOfficio' => $term->via_dutiable_id !== null,
                'roles' => $duty->roles->pluck('name')->values()->all(),
            ];
        })->values()->all();
    }
}
