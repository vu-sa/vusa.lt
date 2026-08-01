<?php

namespace App\Services\ContentResolution\Resolvers;

use App\Models\Calendar;
use App\Models\Category;
use App\Models\ContentPart;
use App\Services\ContentResolution\ResolutionContext;
use App\Services\ContentResolution\ResolvesContentPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Resolves `event-list` blocks: a filtered, optionally tenant-grouped list of Calendar
 * events (see RCEventList/EventListDisplay.vue). Modeled on
 * `PublicPageController::summerCamps()` — the `year` + `freshmen-camps` category +
 * tenant-grouping combination this block generalizes.
 */
final class EventListResolver implements ResolvesContentPart
{
    private const int MAX_ITEMS = 24;

    private const int MAX_TENANT_IDS = 20;

    public function resolve(Collection $parts, ResolutionContext $context): array
    {
        // Each block's date window, category and tenant scope differ, so — unlike
        // LinkListResolver's pinned-id lookups — there is no shared query to batch;
        // one query per block is the correct shape here, not a missed optimization.
        $resolved = [];
        foreach ($parts as $id => $part) {
            $resolved[$id] = $this->resolvePart($part, $context);
        }

        return $resolved;
    }

    /** @return array<string, mixed> */
    private function resolvePart(ContentPart $part, ResolutionContext $context): array
    {
        $options = (array) ($part->options ?? []);
        $mode = in_array($options['mode'] ?? null, ['upcoming', 'range', 'year'], true) ? $options['mode'] : 'upcoming';
        $limit = max(1, min(self::MAX_ITEMS, (int) ($options['limit'] ?? 12)));
        $groupByTenant = ($options['groupBy'] ?? 'none') === 'tenant';
        $style = in_array($options['style'] ?? null, ['cards', 'list'], true) ? $options['style'] : 'cards';
        $tenantLabelPrefix = is_string($options['tenantLabelPrefix'] ?? null) ? $options['tenantLabelPrefix'] : '';
        // 'faculty' → "VU <nominative faculty>" (e.g. "VU Filologijos fakultetas"),
        // derived from the locative `fullname` by stripping the common VU SA prefix and
        // reversing the locative ending. Mirrors the client-side `getFacultyName`
        // (Utils/String.ts) used by SummerCampCard; kept in sync so the two surfaces
        // agree. The central VU SA tenant has no faculty part, so it falls back to its
        // fullname.
        $tenantLabelStyle = ($options['tenantLabelStyle'] ?? 'full') === 'faculty' ? 'faculty' : 'full';

        $query = Calendar::query()->where('is_draft', false)->with(['media', 'tenant:id,alias,fullname,shortname']);

        $alias = $options['categoryAlias'] ?? null;
        if (is_string($alias) && $alias !== '') {
            // The category is a grouping key, not a publication gate — a trashed
            // category (e.g. an old campaign) must still work as one. See the
            // identical rationale in PublicPageController::summerCamps().
            $query->whereHas('category', function (Builder $q) use ($alias): void {
                /** @var Builder<Category> $q */
                $q->withTrashed()->where('alias', $alias);
            });
        }

        if ($context->locale === 'en') {
            $query->where('is_international', true);
        }

        $tenantScope = $options['tenantScope'] ?? 'current';
        if ($tenantScope === 'current') {
            $query->where('tenant_id', $context->tenant->id);
        } elseif (is_array($tenantScope)) {
            $ids = array_slice(array_map(intval(...), array_filter($tenantScope, is_numeric(...))), 0, self::MAX_TENANT_IDS);
            if ($ids !== []) {
                $query->whereIn('tenant_id', $ids);
            }
        }
        // 'all' → no tenant filter.

        [$from, $to] = $this->resolveDateRange($mode, $options);
        $query->whereBetween('date', [$from, $to]);

        // Grouped results need every event in every group up front (the display can't
        // paginate a group), so the row cap is generous; ungrouped results respect the
        // author's limit directly.
        $rowCap = $groupByTenant ? self::MAX_ITEMS * 6 : $limit;
        $events = $query->orderBy('date')->limit($rowCap)->get();

        if ($groupByTenant) {
            $groups = $events->groupBy('tenant_id')->map(function (Collection $tenantEvents) use ($tenantLabelPrefix, $tenantLabelStyle, $context) {
                $tenant = $tenantEvents->first()->tenant;
                $label = $tenantLabelStyle === 'faculty'
                    ? $this->facultyLabel($tenant->fullname)
                    : trim($tenantLabelPrefix.' '.$tenant->fullname);

                return [
                    'key' => (string) $tenant->id,
                    'label' => $label,
                    'items' => $tenantEvents->sortBy('date')->values()->map(fn (Calendar $e) => $this->mapEvent($e, $context))->all(),
                ];
            })
                // Groups previously kept whichever order tenants first appeared in the
                // date-sorted event list — "whoever has the earliest event first" —
                // which reads as arbitrary in a grid of cards. Alphabetical by label is
                // stable and predictable regardless of event dates.
                ->sortBy('label')
                ->values()->all();

            return [
                'type' => 'event-list',
                'groups' => $groups,
                'items' => $events->sortBy('date')->values()->map(fn (Calendar $e) => $this->mapEvent($e, $context))->all(),
                'meta' => ['total' => $events->count(), 'truncated' => false, 'style' => $style],
            ];
        }

        $items = $events->take($limit)->map(fn (Calendar $e) => $this->mapEvent($e, $context))->values()->all();

        return [
            'type' => 'event-list',
            'groups' => [],
            'items' => $items,
            'meta' => ['total' => count($items), 'truncated' => $events->count() > $limit, 'style' => $style],
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveDateRange(string $mode, array $options): array
    {
        if ($mode === 'year') {
            $currentYear = (int) date('Y');
            $year = (int) ($options['year'] ?? $currentYear);
            $year = max(2000, min($currentYear + 2, $year));

            return [Carbon::create($year, 1, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay()];
        }

        if ($mode === 'range') {
            $from = $this->parseDate($options['dateFrom'] ?? null) ?? now()->subDays(7);
            $to = $this->parseDate($options['dateTo'] ?? null) ?? now()->addDays(90);
            if ($to->lessThan($from)) {
                $to = $from->copy()->addDay();
            }
            if ($from->diffInDays($to) > Calendar::MAX_RANGE_DAYS) {
                $to = $from->copy()->addDays(Calendar::MAX_RANGE_DAYS);
            }

            return [$from, $to];
        }

        // upcoming: a rolling window, not "everything from the beginning of time".
        return [now()->subDay(), now()->addDays(180)];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);

            return $date ? $date->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function mapEvent(Calendar $event, ResolutionContext $context): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'date' => optional($event->date)->toIso8601String(),
            'endDate' => optional($event->end_date)->toIso8601String(),
            'location' => $event->location,
            'isAllDay' => (bool) $event->is_all_day,
            'ctoUrl' => $event->cto_url,
            'imageUrl' => $event->main_image_url,
            // 'www' matches the existing SummerCampCard.vue precedent for this route —
            // it's a redirect route that resolves the event's real URL server-side
            // regardless of which subdomain it was reached through.
            'href' => route('calendar.event', ['calendar' => $event->id, 'lang' => $context->locale, 'subdomain' => 'www']),
        ];
    }

    /**
     * Derives a "VU <nominative faculty>" label from the locative tenant fullname,
     * e.g. "VU Filologijos fakultetas" from "... Studentų atstovybė Filologijos fakultete".
     *
     * Server-side port of the client-side `getFacultyName` util (resources/js/Utils/String.ts) —
     * the two must stay in lockstep so SummerCampCard and this resolver render the same names.
     * Tenants without a faculty part (the central VU SA tenant, clubs) fall back to
     * their fullname rather than a malformed "VU " prefix.
     */
    private function facultyLabel(string $fullname): string
    {
        $after = trim(Str::after($fullname, 'Vilniaus universiteto Studentų atstovybė'));
        if ($after === '') {
            return $fullname;
        }

        foreach (['ete' => 'etas', 'tre' => 'tras', 'ykloje' => 'ykla', 'ute' => 'utas', 'joje' => 'ja'] as $from => $to) {
            if (str_ends_with($after, $from)) {
                $after = substr($after, 0, -strlen($from)).$to;

                break;
            }
        }

        return 'VU '.$after;
    }
}
