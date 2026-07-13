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
                $contacts = $duty->current_users->map(function ($user) use ($duty) {
                    return [
                        'user' => $user,
                        'duty' => $duty,
                    ];
                })->toArray();

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
     * @param  Duty  $duty
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupContactsByDuty($duty, string $groupingType): array
    {
        $groups = [];

        $users = $duty->current_users->load([
            'dutiables' => function ($query) use ($duty) {
                $query->where('duty_id', $duty->id)
                    ->with(['study_program.tenant']);
            },
        ]);

        foreach ($users as $user) {
            $dutiable = $user->dutiables->where('duty_id', $duty->id)->first();

            if (! $dutiable) {
                continue;
            }

            $groupKey = $this->getGroupKey($dutiable, $groupingType);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            $groups[$groupKey][] = [
                'user' => $user,
                'duty' => $duty,
                'dutiable' => $dutiable,
            ];
        }

        // Sort groups: named groups first (alphabetically), then "Other"
        uksort($groups, function ($a, $b) {
            if ($a === 'Other') {
                return 1;
            }

            if ($b === 'Other') {
                return -1;
            }

            return strcmp($a, $b);
        });

        return $groups;
    }

    /**
     * Get the group key based on grouping type.
     *
     * @param  Dutiable  $dutiable
     */
    public function getGroupKey($dutiable, string $groupingType): string
    {
        switch ($groupingType) {
            case 'study_program':
                return $dutiable->study_program ? $dutiable->study_program->name : 'Other';

            case 'tenant':
                return $dutiable->study_program
                    ? $dutiable->study_program->tenant->shortname
                    : 'Other';

            default:
                return 'Other';
        }
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
