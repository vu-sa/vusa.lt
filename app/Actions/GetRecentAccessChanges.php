<?php

namespace App\Actions;

use App\Models\Pivots\Dutiable;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * "Tavo prieigos pasikeitė" (U14): the duty terms that began or ended for a user lately.
 *
 * One source for the notice command, the Pradžia band and the history on *Mano rolės*. Read from the
 * dutiable dates, because end-dating a term is not activity-logged. A term's last day is still
 * access, so it *ended* the day after `end_date`; `effectiveOn` is that day, `date` the one to show.
 */
class GetRecentAccessChanges
{
    public const string STARTED = 'started';

    public const string ENDED = 'ended';

    /**
     * @return list<array{kind: string, dutyName: string, institutionName: string|null, date: string, effectiveOn: string, isExOfficio: bool}>
     */
    public static function execute(User $user, int $days, ?CarbonInterface $now = null, ?int $limit = null): array
    {
        $today = ($now ?? now())->copy()->startOfDay();
        $from = $today->copy()->subDays($days);

        /** @var Collection<int, Dutiable> $terms */
        $terms = $user->dutiables()
            ->with('duty.institution')
            ->where(function ($query) use ($from, $today): void {
                $query->whereBetween('start_date', [$from->toDateString(), $today->toDateString()])
                    ->orWhereBetween('end_date', [
                        $from->copy()->subDay()->toDateString(),
                        $today->copy()->subDay()->toDateString(),
                    ]);
            })
            ->get()
            ->filter(fn (Dutiable $term): bool => $term->duty !== null);

        $changes = [];

        foreach ($terms as $term) {
            $started = $term->start_date->copy()->startOfDay();

            if ($started->betweenIncluded($from, $today)) {
                $changes[] = self::change($term, self::STARTED, $started, $started);
            }

            $lastDay = $term->end_date?->copy()->startOfDay();

            if ($lastDay !== null && $lastDay->copy()->addDay()->betweenIncluded($from, $today)) {
                $changes[] = self::change($term, self::ENDED, $lastDay, $lastDay->copy()->addDay());
            }
        }

        usort($changes, fn (array $a, array $b): int => [$b['effectiveOn'], $a['dutyName']] <=> [$a['effectiveOn'], $b['dutyName']]);

        return array_slice($changes, 0, $limit);
    }

    /**
     * @return array{kind: string, dutyName: string, institutionName: string|null, date: string, effectiveOn: string, isExOfficio: bool}
     */
    private static function change(Dutiable $term, string $kind, CarbonInterface $date, CarbonInterface $effectiveOn): array
    {
        return [
            'kind' => $kind,
            'dutyName' => (string) $term->duty->name,
            'institutionName' => $term->duty->institution?->name,
            'date' => $date->toDateString(),
            'effectiveOn' => $effectiveOn->toDateString(),
            'isExOfficio' => $term->via_dutiable_id !== null,
        ];
    }
}
