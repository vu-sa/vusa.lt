<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Type;
use Illuminate\Support\Collection;

class ContactPresentationService
{
    /**
     * Process duties with individual grouping settings.
     *
     * @param  Collection<int, Duty>  $duties
     * @return array<int, array<string, mixed>>
     */
    public function processDutiesWithGrouping($duties): array
    {
        $result = [];

        foreach ($duties as $duty) {
            if ($duty->contacts_grouping && $duty->contacts_grouping !== 'none') {
                $groups = $this->groupContactsByDuty($duty, $duty->contacts_grouping);

                if (! empty($groups)) {
                    // If every contact fell into the fallback group, the grouping
                    // carries no information — render the duty flat instead.
                    if (count($groups) === 1 && array_key_first($groups) === $this->fallbackGroupName()) {
                        $result[] = [
                            'type' => 'flat_duty',
                            'dutyName' => $duty->name,
                            'duty' => $duty,
                            'contacts' => collect($groups[$this->fallbackGroupName()])
                                ->map(fn ($item) => ['user' => $item['user'], 'duty' => $item['duty']])
                                ->all(),
                        ];

                        continue;
                    }

                    $transformedGroups = [];
                    foreach ($groups as $groupName => $contacts) {
                        $transformedGroups[] = [
                            'name' => $groupName,
                            'contacts' => $contacts,
                        ];
                    }

                    $result[] = [
                        'type' => 'grouped_duty',
                        'dutyName' => $duty->name,
                        'duty' => $duty,
                        'groups' => $transformedGroups,
                    ];
                }
            } else {
                $contacts = $duty->current_users->map(fn ($user) => [
                    'user' => $user,
                    'duty' => $duty,
                ])->toArray();

                if (! empty($contacts)) {
                    $result[] = [
                        'type' => 'flat_duty',
                        'dutyName' => $duty->name,
                        'duty' => $duty,
                        'contacts' => $contacts,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Group contacts for a single duty by study program or tenant.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupContactsByDuty(Duty $duty, string $groupingType): array
    {
        $groups = [];

        $users = $duty->current_users->load([
            'dutiables' => function ($query) use ($duty): void {
                // Only active rows drive grouping, mirroring current_users semantics —
                // otherwise a member's ended row could win over their current one.
                $query->where('duty_id', $duty->id)
                    ->where(function ($q): void {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    })
                    ->with(['study_program.tenant', 'tenant']);
            },
        ]);

        foreach ($users as $user) {
            $dutiable = $user->dutiables->where('duty_id', $duty->id)->first();

            if (! $dutiable) {
                continue;
            }

            $groupKey = $this->getGroupKey($dutiable, $groupingType, $duty);

            $groups[$groupKey] ??= [];

            $groups[$groupKey][] = [
                'user' => $user,
                'duty' => $duty,
                'dutiable' => $dutiable,
            ];
        }

        // Sort groups: named groups first (alphabetically), then the fallback group
        $fallback = $this->fallbackGroupName();
        uksort($groups, function ($a, $b) use ($fallback) {
            if ($a === $fallback) {
                return 1;
            }

            if ($b === $fallback) {
                return -1;
            }

            return strcmp($a, $b);
        });

        return $groups;
    }

    /**
     * Get the group key based on grouping type.
     */
    public function getGroupKey(Dutiable $dutiable, string $groupingType, ?Duty $duty = null): string
    {
        return match ($groupingType) {
            'study_program' => $dutiable->study_program
                ? $dutiable->study_program->name
                : $this->fallbackGroupName(),
            // The padalinys the member represents: the cross-tenant assignment
            // (dutiables.tenant_id), or the duty's own tenant when null.
            'tenant' => $dutiable->tenant?->shortname // @phpstan-ignore nullsafe.neverNull
                ?? $duty?->loadMissing('institution.tenant')->institution?->tenant?->shortname // @phpstan-ignore nullsafe.neverNull
                ?? $this->fallbackGroupName(),
            default => $this->fallbackGroupName(),
        };
    }

    /**
     * Name of the group for contacts that cannot be grouped (translated).
     */
    private function fallbackGroupName(): string
    {
        return __('Kita');
    }

    /**
     * Filter processed contacts to only show duties related to the selected types.
     *
     * @param  array<int, array<string, mixed>>  $processedContacts
     * @param  \Illuminate\Database\Eloquent\Collection<int, Type>  $types
     * @return array<int, array<string, mixed>>
     */
    public function filterProcessedContactsByTypes(array $processedContacts, $types): array
    {
        $filteredSections = [];

        foreach ($processedContacts as $section) {
            $dutyHasMatchingTypes = $section['duty']->types->intersect($types)->count() > 0;

            if ($dutyHasMatchingTypes) {
                $filteredSections[] = $section;
            }
        }

        return $filteredSections;
    }
}
