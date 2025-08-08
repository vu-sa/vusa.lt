<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContactController extends PublicController
{
    public function contacts()
    {
        $this->getTenantLinks();
        $this->shareOtherLangURL('contacts', $this->subdomain);

        $tenants = json_decode(base64_decode(request()->input('selectedTenants'))) ??
            collect([Tenant::query()->where('type', 'pagrindinis')->first()->id, $this->tenant->id])->unique();

        $institutions = Institution::query()->with('tenant', 'types:id,title,model_type,slug')
            ->whereHas('tenant', fn ($query) => $query->whereIn('id', $tenants)->select(['id', 'shortname', 'alias'])
            )->withCount('duties')->orderBy('name')->get()->makeHidden(['created_at', 'updated_at', 'deleted_at']);

        $seo = $this->shareAndReturnSEOObject(
            title: __('Kontaktų paieška').' - '.$this->tenant->shortname,
            description: app()->getLocale() === 'lt' ? 'VU SA kontaktų paieškoje vienoje vietoje suraskite visus VU SA kontaktus' : 'In the VU SA contact search, find all VU SA contacts in one place', );

        return Inertia::render('Public/Contacts/ContactsSearch', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    // shorten description and add ellipsis
                    // 'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                    //  TODO: better solution for displaying description or remove completely
                    'description' => '',
                ];
            }),
            'selectedTenants' => $tenants,
        ])->withViewData(
            ['SEOData' => $seo]
        );
    }

    public function institutionContacts($subdomain, $lang, Institution $institution)
    {
        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('contacts.institution', ['subdomain' => $this->subdomain, 'lang' => $this->getOtherLang(), 'institution' => $institution->id]));

        $duties = $institution->load('duties.current_users.current_duties')->duties->sortBy(function ($duty) {
            return $duty->order;
        });

        // Process duties in order and group/flatten as needed
        $allContacts = collect();
        $hasGroupedDuties = false;

        foreach ($duties as $duty) {
            // Check if this duty should be grouped
            if ($duty->contacts_grouping && $duty->contacts_grouping !== 'none') {
                $hasGroupedDuties = true;
                break;
            }
        }

        if ($hasGroupedDuties) {
            $processedContacts = $this->processDutiesWithGrouping($duties);

            return $this->showInstitutionWithMixedContacts($institution, $processedContacts, $institution->name.' | Kontaktai');
        }

        // Default behavior - flatten and deduplicate all duties
        $contacts = $duties->pluck('current_users')->flatten()->unique('id')->values();

        // make eloquent collection from array
        $contacts = new Collection($contacts);

        return $this->showInstitution($institution, $contacts, $institution->name.' | Kontaktai');
    }

    public function institutionDutyTypeContacts($subdomain, $lang, Type $type)
    {
        $this->getTenantLinks();
        Inertia::share('otherLangURL', route('contacts.dutyType', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(), 'type' => $type->slug]));

        $types = $type->getDescendantsAndSelf();

        if ($this->tenant->type === 'pagrindinis') {
            $institution = Institution::where('alias', '=', 'centrinis-biuras')->first();
        } else {
            $institution = Institution::where('alias', '=', $this->tenant->alias)->firstOrFail();
        }

        // load duties whereHas types
        $duties = $institution->load(['duties' => function ($query) use ($types) {
            $query->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id')))->with('current_users.current_duties');
        }])->duties->sortBy(function ($duty) {
            return $duty->order;
        });

        // Process duties in order and group/flatten as needed
        $hasGroupedDuties = false;

        foreach ($duties as $duty) {
            // Check if this duty should be grouped
            if ($duty->contacts_grouping && $duty->contacts_grouping !== 'none') {
                $hasGroupedDuties = true;
                break;
            }
        }

        if ($hasGroupedDuties) {
            $processedContacts = $this->processDutiesWithGrouping($duties);

            // Filter processed contacts to only show duties related to the selected types
            $processedContacts = $this->filterProcessedContactsByTypes($processedContacts, $types);

            return $this->showInstitutionWithMixedContacts($institution, $processedContacts, $institution->name.' | '.ucfirst($type->slug));
        }

        // Default behavior - flatten and deduplicate all duties
        $contacts = $duties->pluck('current_users')->flatten()->unique('id');

        // keep all contacts, but remove some duties from them, if they are not in the selected types
        $contacts = $contacts->map(function ($contact) use ($types) {
            // You can't overwrite the relations, so we need to use another name
            $contact->filtered_current_duties = $contact->current_duties->filter(function ($duty) use ($types) {
                return $duty->types->intersect($types)->count() > 0;
            });

            return $contact;
        });

        // make eloquent collection from array
        $contacts = new Collection($contacts);

        return $this->showInstitution($institution, $contacts, $institution->name.' | '.ucfirst($type->slug));
    }

    public function studentRepresentatives()
    {
        $this->getTenantLinks();
        $this->shareOtherLangURL('contacts.studentRepresentatives', $this->subdomain);

        $type = Type::query()->where('slug', '=', 'studentu-atstovu-organas')->first();
        $descendants = $type->getDescendantsAndSelf();

        $descendants->load(['institutions' => function ($query) {
            $query
                ->with('duties.current_users:id,name,email,phone,facebook_url,profile_photo_path')
                ->with('tenant:id,alias')
                ->where('tenant_id', '=', $this->tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'alias', 'description']);
        }]);

        // remove descendants without institutions
        $descendants = $descendants->filter(function ($descendant) {
            /** @var Type $descendant */
            return $descendant->institutions->count() > 0;
        })->values();

        $seo = $this->shareAndReturnSEOObject(
            title: __('Studentų atstovai').' - '.$this->tenant->shortname,
            description: app()->getLocale() === 'lt' ? $this->tenant->shortname.' studentų atstovų paieškoje vienoje vietoje suraskite visus '.$this->tenant->shortname.'studentų atstovus' : 'In '.$this->tenant->shortname.'contact search find all'.$this->tenant->shortname.'student representatives');

        return Inertia::render('Public/Contacts/ShowStudentReps', [
            'types' => $descendants,
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }

    private function showInstitution(Institution $institution, Collection $contacts, string $title)
    {
        $seo = $this->shareAndReturnSEOObject(
            title: $title.' - '.$this->tenant->shortname,
            description: Str::limit(strip_tags($institution->description), 160),
            image: $institution->image_url,
        );

        return Inertia::render('Public/Contacts/ContactInstitutionOrType', [
            'institution' => $institution,
            'contacts' => $contacts->map(function ($contact) use ($institution) {
                /** @var User $contact */
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'facebook_url' => $contact->facebook_url,
                    // Sometimes the duties may be filtered, e.g. curator duties are not shown in coordinator
                    'duties' => isset($contact->filtered_current_duties) ? $contact->filtered_current_duties->where('institution_id', '=', $institution->id)->values() :
                    $contact->current_duties->where('institution_id', '=', $institution->id)->values(),
                    'profile_photo_path' => $contact->profile_photo_path,
                    'pronouns' => $contact->pronouns,
                    'show_pronouns' => $contact->show_pronouns,
                ];
            }),
        ])->withViewData(
            ['SEOData' => $seo]
        );
    }

    /**
     * contactsCategory
     *
     * @param  string  $subdomain
     * @param  string  $lang
     * @return \Inertia\Response
     */
    public function institutionCategory($subdomain, $lang, Type $type)
    {
        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('contacts.category', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(), 'type' => $type->slug]));

        $institutions = $type->load(['institutions' => function ($query) {
            $query->orderBy('name')->with(['tenant' => function ($query) {
                $query->where('type', 'padalinys');
            }]);
        }])->institutions;

        $seo = $this->shareAndReturnSEOObject(
            title: __('Kontaktai').': '.$type->title.' - VU SA',
            description: Str::limit($type->description, 160),
        );

        return Inertia::render('Public/Contacts/ShowContactCategory', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    // shorten description and add ellipsis
                    // 'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                    //  TODO: better solution for displaying description or remove completely
                    'description' => '',
                ];
            }),
            'type' => $type->unsetRelation('institutions'),
        ])->withViewData(
            ['SEOData' => $seo]
        );
    }

    /**
     * Process duties with individual grouping settings
     */
    private function processDutiesWithGrouping($duties): array
    {
        $result = [];

        foreach ($duties as $duty) {
            if ($duty->contacts_grouping && $duty->contacts_grouping !== 'none') {
                // This duty needs grouping - collect all groups for this duty
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
                // This duty doesn't need grouping, add contacts directly
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
     * Group contacts for a single duty by study program or tenant
     */
    private function groupContactsByDuty($duty, string $groupingType): array
    {
        $groups = [];

        // Load current users with their dutiables and study programs
        $users = $duty->current_users->load([
            'dutiables' => function ($query) use ($duty) {
                $query->where('duty_id', $duty->id)
                    ->with(['study_program.tenant']);
            },
        ]);

        foreach ($users as $user) {
            // Find the dutiable for this specific duty
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
     * Get the group key based on grouping type
     */
    private function getGroupKey($dutiable, string $groupingType): string
    {
        switch ($groupingType) {
            case 'study_program':
                return $dutiable->study_program ? $dutiable->study_program->name : 'Other';

            case 'tenant':
                return $dutiable->study_program && $dutiable->study_program->tenant
                    ? $dutiable->study_program->tenant->shortname
                    : 'Other';

            default:
                return 'Other';
        }
    }

    /**
     * Show institution with mixed grouped and flat contacts
     */
    private function showInstitutionWithMixedContacts(Institution $institution, array $processedContacts, string $title)
    {
        $seo = $this->shareAndReturnSEOObject(
            title: $title.' - '.$this->tenant->shortname,
            description: Str::limit(strip_tags($institution->description), 160),
            image: $institution->image_url,
        );

        // Transform processed contacts for frontend
        $transformedSections = [];
        foreach ($processedContacts as $section) {
            if ($section['type'] === 'grouped_duty') {
                // This is a duty with grouped contacts
                $transformedGroups = [];
                foreach ($section['groups'] as $group) {
                    $transformedGroups[] = [
                        'name' => $group['name'],
                        'contacts' => collect($group['contacts'])->map(function ($item) {
                            return [
                                'id' => $item['user']->id,
                                'name' => $item['user']->name,
                                'email' => $item['user']->email,
                                'phone' => $item['user']->phone,
                                'facebook_url' => $item['user']->facebook_url,
                                'duties' => [$item['duty']->only(['id', 'name', 'description'])],
                                'profile_photo_path' => $item['user']->profile_photo_path,
                                'pronouns' => $item['user']->pronouns,
                                'show_pronouns' => $item['user']->show_pronouns,
                            ];
                        })->values()->toArray(),
                    ];
                }

                $transformedSections[] = [
                    'type' => 'grouped_duty',
                    'dutyName' => $section['duty']->name, // Use the duty model directly for translation
                    'groups' => $transformedGroups,
                ];
            } else {
                // This is a flat duty
                $transformedSections[] = [
                    'type' => 'flat_duty',
                    'dutyName' => $section['duty']->name, // Use the duty model directly for translation
                    'contacts' => collect($section['contacts'])->map(function ($item) {
                        return [
                            'id' => $item['user']->id,
                            'name' => $item['user']->name,
                            'email' => $item['user']->email,
                            'phone' => $item['user']->phone,
                            'facebook_url' => $item['user']->facebook_url,
                            'duties' => [$item['duty']->only(['id', 'name', 'description'])],
                            'profile_photo_path' => $item['user']->profile_photo_path,
                            'pronouns' => $item['user']->pronouns,
                            'show_pronouns' => $item['user']->show_pronouns,
                        ];
                    })->values()->toArray(),
                ];
            }
        }

        return Inertia::render('Public/Contacts/ContactInstitutionOrType', [
            'institution' => $institution,
            'contactSections' => $transformedSections,
            'hasMixedGrouping' => true,
        ])->withViewData(
            ['SEOData' => $seo]
        );
    }

    /**
     * Filter processed contacts to only show duties related to the selected types
     */
    private function filterProcessedContactsByTypes(array $processedContacts, $types): array
    {
        $typeIds = $types->pluck('id');
        $filteredSections = [];

        foreach ($processedContacts as $section) {
            if ($section['type'] === 'grouped_duty') {
                // Check if this duty has any of the selected types
                $dutyHasMatchingTypes = $section['duty']->types->intersect($types)->count() > 0;

                if ($dutyHasMatchingTypes) {
                    $filteredSections[] = $section;
                }
            } else {
                // For flat duties, check the same way
                $dutyHasMatchingTypes = $section['duty']->types->intersect($types)->count() > 0;

                if ($dutyHasMatchingTypes) {
                    $filteredSections[] = $section;
                }
            }
        }

        return $filteredSections;
    }
}
