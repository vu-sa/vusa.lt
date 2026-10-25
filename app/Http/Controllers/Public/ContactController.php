<?php

namespace App\Http\Controllers\Public;

use App\Actions\GetPublicMeetingDocuments;
use App\Enums\SystemDutyType;
use App\Enums\TenantType;
use App\Http\Controllers\PublicController;
use App\Models\DutyType;
use App\Models\Form;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\AgendaItemPresenter;
use App\Services\ContactPresentationService;
use App\Services\PublicAssetService;
use App\Services\SystemDutyTypes;
use App\Settings\AtstovavimasSettings;
use App\Settings\FormSettings;
use App\Support\MeetingTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends PublicController
{
    public function __construct(private readonly ContactPresentationService $presentationService, PublicAssetService $publicAssets)
    {
        parent::__construct($publicAssets);
    }

    public function contacts()
    {
        $this->getTenantLinks();
        $this->shareOtherLangURL('contacts', $this->subdomain);

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: __('Kontaktų paieška'),
            description: __('search.institution_page_description'),
        );

        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $studentRepTypeSlugs = $atstovavimasSettings->getStudentRepInstitutionTypeSlugs()->toArray();

        return Inertia::render('Public/Contacts/ShowContacts', [
            'tenantSwitchTarget' => 'same-page',
            'institutionTypes' => $this->institutionTypeTree(),
            'studentRepTypeSlugs' => $studentRepTypeSlugs,
        ]);
    }

    /**
     * Types in use plus their ancestors, so the page can expand a parent filter to its sub-types.
     * Resolved per request rather than indexed: re-parenting a type then needs no reindex.
     *
     * @return list<array{slug: string, title: string, parent_slug: string|null}>
     */
    private function institutionTypeTree(): array
    {
        $types = InstitutionType::query()->whereHas('institutions')->with('recursiveParent')->get();

        return $types
            ->flatMap(function (InstitutionType $type): array {
                $lineage = [];

                for ($current = $type; $current !== null; $current = $current->recursiveParent) {
                    $lineage[] = $current;
                }

                return $lineage;
            })
            ->filter(fn (InstitutionType $type): bool => filled($type->slug))
            ->unique('id')
            ->map(fn (InstitutionType $type): array => [
                'slug' => $type->slug,
                'title' => $type->getTranslation('title', app()->getLocale()),
                'parent_slug' => $type->recursiveParent?->slug,
            ])
            ->values()
            ->all();
    }

    public function institutionContacts($subdomain, $lang, $contactsString, Institution $institution)
    {
        $this->getTenantLinks();

        Inertia::share('otherLangURL', $institution->publicUrl($this->getOtherLang())
            ?? route('contacts.institution', ['subdomain' => $this->subdomain, 'lang' => $this->getOtherLang(), 'institution' => $institution->id]));

        // Load types for breadcrumb navigation
        $institution->load('types');

        $this->sharePublicEditLink($institution);

        $duties = $institution->duties()->with(['current_users', 'types'])->get();

        return $this->renderInstitutionPage(
            $institution,
            $this->presentationService->contactsFor($institution, $duties),
            $institution->name,
            activeDutyTypeTab: 'all',
            canonicalUrl: $institution->publicUrl(app()->getLocale()),
        );
    }

    public function institutionDutyTypeContacts($subdomain, $lang, $contactsString, string $type)
    {
        $part = SystemDutyType::fromPublicSegment($type);
        $dutyType = $part === null ? null : app(SystemDutyTypes::class)->type($part);

        abort_if($dutyType === null, 404);

        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('contacts.dutyType', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(),
            'type' => $part->forLocale($this->getOtherLang())->publicSegment(),
        ]));

        $types = $dutyType->getDescendantsAndSelf();

        if ($this->tenant->isMain()) {
            $institution = Institution::where('alias', '=', 'centrinis-biuras')->firstOrFail();
        } else {
            $institution = Institution::where('alias', '=', $this->tenant->alias)->firstOrFail();
        }

        // Load types for breadcrumb navigation
        $institution->load('types');

        $duties = $institution->duties()
            ->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id')))
            ->with(['current_users', 'types'])
            ->get();

        return $this->renderInstitutionPage(
            $institution,
            $this->presentationService->contactsFor($institution, $duties),
            ($part === SystemDutyType::Coordinator ? __('Koordinatoriai') : __('Kuratoriai')).': '.$institution->name,
            activeDutyTypeTab: $part->publicSegment(),
        );
    }

    public function studentRepresentatives()
    {
        $this->getTenantLinks();
        $this->shareOtherLangURL('contacts.studentRepresentatives', $this->subdomain);

        $type = app(AtstovavimasSettings::class)->getStudentRepRootType();
        /** @var Collection<int, InstitutionType> $descendants */
        $descendants = $type ? $type->getDescendantsAndSelf() : collect();

        $descendants->load(['institutions' => function ($query): void {
            $query
                ->with('duties.current_users:id,name,email,phone,facebook_url,profile_photo_path,profile_photo_focal_point')
                ->with('tenant:id,alias')
                ->where('tenant_id', '=', $this->tenant->id)
                ->where('is_active', true)
                ->orderBy('name');
        }]);

        // remove descendants without institutions
        $descendants = $descendants->filter(function ($descendant) {
            /** @var InstitutionType $descendant */
            return $descendant->institutions->count() > 0;
        })->values();

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: __('Studentų atstovai'),
            description: __('seo.student_reps_description', ['tenant' => $this->tenant->shortname]),
        );

        return Inertia::render('Public/Contacts/ShowStudentReps', [
            'tenantSwitchTarget' => 'same-page',
            'types' => $descendants,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $contacts  from ContactPresentationService::contactsFor()
     */
    private function renderInstitutionPage(
        Institution $institution,
        array $contacts,
        string $title,
        ?string $activeDutyTypeTab = 'all',
        ?string $canonicalUrl = null,
    ) {
        abort_unless($institution->is_active === 1, 404);

        $institution->loadMissing('tenant');

        // Load meetings and group by academic year
        $meetings = $this->getAllMeetingsForInstitution($institution);
        $groupedMeetings = $this->groupMeetingsByAcademicYear($meetings);

        // Compute duty type tabs for padalinys main institution
        $dutyTypeTabs = [];
        $isPadalinys = $institution->tenant?->type === TenantType::Padalinys;

        if ($isPadalinys) {
            $systemTypes = app(SystemDutyTypes::class);
            $parts = collect([SystemDutyType::Coordinator, SystemDutyType::Curator->forLocale(app()->getLocale())])
                ->filter(fn (SystemDutyType $part): bool => DutyType::query()->whereIn('id', $systemTypes->idsWithDescendants($part))
                    ->whereHas('duties', fn ($q) => $q->where('institution_id', $institution->id))
                    ->exists())
                ->values();

            if ($parts->isNotEmpty()) {
                $dutyTypeTabs = [
                    [
                        'label' => __('Visi'),
                        'slug' => 'all',
                        'href' => route('contacts.alias', [
                            'subdomain' => $this->subdomain,
                            'lang' => app()->getLocale(),
                            'institution' => $institution->alias,
                        ]),
                    ],
                ];

                foreach ($parts as $part) {
                    $dutyTypeTabs[] = [
                        'label' => $part === SystemDutyType::Coordinator ? __('Koordinatoriai') : __('Kuratoriai'),
                        'slug' => $part->publicSegment(),
                        'href' => route('contacts.dutyType', [
                            'subdomain' => $this->subdomain,
                            'lang' => app()->getLocale(),
                            'type' => $part->publicSegment(),
                        ]),
                    ];
                }
            }
        }

        // PKP tenants have no site of their own; their institutions live on www.
        $siteTenant = $institution->tenant?->type === TenantType::Pkp ? Tenant::main() : $institution->tenant;

        $this->applyPageHead(
            contentTenant: $siteTenant,
            title: $title,
            description: [
                $institution->description,
                __('seo.institution_description', ['institution' => $institution->name, 'tenant' => $siteTenant->shortname ?? $this->tenant->shortname]),
            ],
            image: $institution->imageData('image')['url'] ?? null,
            canonicalUrl: $canonicalUrl,
        );

        $data = [
            // The contacts are passed on their own; the duty tree would be serialized twice.
            // The page reads image_url; it comes from the image's media now.
            'institution' => $institution->unsetRelation('duties')
                ->setAttribute('image_url', $institution->imageData('image')['url'] ?? null)
                ->setAttribute('image_media', $institution->imageData('image'))
                ->unsetRelation('media'),
            'currentYearMeetings' => $groupedMeetings['current'] ?? null,
            'previousYearsMeetings' => $groupedMeetings['previous'] ?? [],
            'hasMeetings' => ! empty($groupedMeetings),
            'studentRepFormInfo' => $this->getStudentRepFormInfo($institution),
            'dutyTypeTabs' => $dutyTypeTabs,
            'activeDutyTypeTab' => $activeDutyTypeTab,
            'contacts' => $contacts,
        ];

        return Inertia::render('Public/Contacts/ShowInstitution', $data);
    }

    /**
     * Show individual meeting detail page
     */
    public function showMeeting($subdomain, $lang, $meetingsString, Meeting $meeting)
    {
        $this->getTenantLinks();

        $meeting->load([
            'agendaItems' => function ($query): void {
                $query->orderBy('order')->orderBy('start_time');
            },
            'agendaItems.votes',
            'institutions.types',
            'calendarEvent',
        ]);

        // Settings-only — see Meeting::isPubliclyVisible().
        abort_unless($meeting->isPubliclyVisible(), 404);

        $this->sharePublicEditLink($meeting);

        $documents = GetPublicMeetingDocuments::execute($meeting);

        // Get primary institution for breadcrumbs
        $primaryInstitution = $meeting->institutions->first();

        // Get representatives who were active at meeting time
        $representatives = $meeting->getRepresentativesActiveAt();

        // Get previous and next meetings for the same institution
        $previousMeeting = Meeting::query()
            ->whereHas('institutions', fn ($q) => $q->where('institutions.id', $primaryInstitution->id))
            ->where('start_time', '<', $meeting->start_time)
            ->orderBy('start_time', 'desc')
            ->select(['id', 'start_time', 'type'])
            ->first();

        $nextMeeting = Meeting::query()
            ->whereHas('institutions', fn ($q) => $q->where('institutions.id', $primaryInstitution->id))
            ->where('start_time', '>', $meeting->start_time)
            ->orderBy('start_time', 'asc')
            ->select(['id', 'start_time', 'type'])
            ->first();

        Inertia::share('otherLangURL', route('publicMeetings.show', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(),
            'meeting' => $meeting->id,
        ]));

        // Use the institution's tenant for proper canonical URL. The title is suffixed with
        // the institution's name rather than the (possibly different) tenant's shortname.
        $this->applyPageHead(
            contentTenant: $primaryInstitution->tenant,
            title: MeetingTitle::for($meeting, app()->getLocale()),
            titleSuffix: ' - '.$primaryInstitution->name,
            description: $meeting->description,
            robots: 'noindex, nofollow',
        );

        return Inertia::render('Public/Meetings/ShowMeeting', [
            'meeting' => AgendaItemPresenter::publicMeeting($meeting),
            'institution' => $primaryInstitution,
            'representatives' => $representatives,
            'previousMeeting' => $previousMeeting,
            'nextMeeting' => $nextMeeting,
            'documents' => $documents,
            'requiresStudentPerspective' => $meeting->requiresStudentPerspective(),
            'calendarEvent' => $meeting->calendarEvent?->is_draft === false
                ? $meeting->calendarEvent->only(['id', 'title', 'date'])
                : null,
        ]);
    }

    /**
     * contactsCategory
     *
     * @param  string  $subdomain
     * @param  string  $lang
     * @return Response
     */
    public function institutionCategory($subdomain, $lang, $contactsString, $contactCategoryString, InstitutionType $type)
    {
        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('contacts.category', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(), 'type' => $type->slug]));

        // For padaliniai type, use the traditional ShowContactCategory view
        if ($type->slug === 'padaliniai') {
            $institutions = $type->load(['institutions' => function ($query): void {
                $query->where('is_active', true)->orderBy('name')->with(['tenant' => function ($query): void {
                    $query->where('type', TenantType::Padalinys);
                }, 'types']);
            }])->institutions;

            $this->applyPageHead(
                contentTenant: $this->tenant,
                title: __('Kontaktai').': '.$type->title,
                description: [$type->description, __('seo.contact_category_description', ['category' => $type->title, 'tenant' => $this->tenant->shortname])],
            );

            return Inertia::render('Public/Contacts/ShowContactCategory', [
                'tenantSwitchTarget' => 'same-page',
                'institutions' => $institutions->map(fn ($institution) => [
                    ...$institution->toArray(),
                    'description' => '',
                ]),
                'type' => $type->unsetRelation('institutions'),
            ]);
        }

        // For other types (pkp, studentu-atstovu-organas), use ShowStudentReps format
        $descendants = $type->getDescendantsAndSelf();

        // Default to showing all tenants on www (pagrindinis), filter by tenant on padalinys subdomains
        // Can be overridden with ?all=1 or ?all=0 query parameter
        $isMainTenant = $this->tenant->isMain();
        $showAllTenants = request()->has('all') ? request()->boolean('all') : $isMainTenant;

        $descendants->load(['institutions' => function ($query) use ($showAllTenants): void {
            $query
                ->with('duties.current_users:id,name,email,phone,facebook_url,profile_photo_path,profile_photo_focal_point')
                ->with('tenant:id,alias,shortname,type')
                ->where('is_active', true)
                // Central-office institutions first, then by name.
                ->orderByRaw(
                    'CASE WHEN EXISTS (SELECT 1 FROM tenants WHERE tenants.id = institutions.tenant_id AND tenants.type = ?) THEN 0 ELSE 1 END',
                    [TenantType::Pagrindinis->value]
                )
                ->orderBy('name');

            // Only filter by tenant if not showing all
            if (! $showAllTenants) {
                $query->where('tenant_id', '=', $this->tenant->id);
            }
        }]);

        $descendants = $descendants->filter(fn (InstitutionType $descendant): bool => $descendant->institutions->isNotEmpty())->values();

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: __('Kontaktai').': '.$type->title,
            description: [$type->description, __('seo.contact_category_description', ['category' => $type->title, 'tenant' => $this->tenant->shortname])],
        );

        return Inertia::render('Public/Contacts/ShowStudentReps', [
            'tenantSwitchTarget' => 'same-page',
            'types' => $descendants,
            'categoryType' => $type->only(['id', 'slug', 'title', 'description']),
            'showAllTenants' => $showAllTenants,
        ]);
    }

    /**
     * Get all meetings for an institution (for grouping by academic year)
     */
    protected function getAllMeetingsForInstitution(Institution $institution): Collection
    {
        if (! $institution->has_public_meetings) {
            return new Collection([]);
        }

        return $institution->meetings()
            ->with([
                'agendaItems' => function ($query): void {
                    $query->orderBy('order');
                },
                'agendaItems.votes',
                // Keeps requires_student_perspective appendable without a query per meeting
                'institutions.types',
            ])
            ->where('start_time', '<=', now())
            ->orderBy('start_time', 'desc')
            ->get()
            ->each(fn (Meeting $meeting) => $meeting->append('completion_status', 'requires_student_perspective'));
    }

    /**
     * Group meetings by academic year (Sept 1 - Aug 31)
     *
     * @param  Collection<int, Meeting>  $meetings
     */
    protected function groupMeetingsByAcademicYear(Collection $meetings): array
    {
        if ($meetings->isEmpty()) {
            return [];
        }

        $grouped = $meetings->groupBy(fn (Meeting $meeting) => $this->getAcademicYear($meeting->start_time));

        $currentAcademicYear = $this->getAcademicYear(now());

        $result = [
            'current' => null,
            'previous' => [],
        ];

        foreach ($grouped as $year => $yearMeetings) {
            $yearData = [
                'year_key' => $year,
                'year_label' => $year.' mokslo metai',
                'meetings' => $yearMeetings->map(fn (Meeting $meeting): array => AgendaItemPresenter::publicMeeting($meeting))->values(),
            ];

            if ($year === $currentAcademicYear) {
                $result['current'] = $yearData;
            } else {
                $result['previous'][] = $yearData;
            }
        }

        return $result;
    }

    /**
     * Calculate academic year from date (Sept 1 boundary)
     *
     * @param  string|Carbon  $date
     */
    protected function getAcademicYear($date): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // If before September 1, use previous year
        // Example: 2024-05-15 → "2023-2024"
        // Example: 2024-09-15 → "2024-2025"
        $startYear = $carbon->month >= 9 ? $carbon->year : $carbon->year - 1;

        return "{$startYear}-".($startYear + 1);
    }

    /**
     * Get student representative registration form info for an institution.
     *
     * Returns null if:
     * - No student rep form is configured
     * - The institution type is not in the allowed types
     *
     * @return array{formPath: string, institutionId: string}|null
     */
    protected function getStudentRepFormInfo(Institution $institution): ?array
    {
        $formSettings = app(FormSettings::class);

        // Check if a form is configured
        if (! $formSettings->student_rep_registration_form_id) {
            return null;
        }

        // Check if institution type is in allowed types
        $allowedTypeIds = $formSettings->getStudentRepInstitutionTypeIds();

        if ($allowedTypeIds->isNotEmpty()) {
            // Load types if not already loaded
            if (! $institution->relationLoaded('types')) {
                $institution->load('types');
            }

            $institutionTypeIds = $institution->types->pluck('id');
            $hasAllowedType = $institutionTypeIds->intersect($allowedTypeIds)->isNotEmpty();

            if (! $hasAllowedType) {
                return null;
            }
        }

        // Get the form path
        $form = Form::find($formSettings->student_rep_registration_form_id);

        if (! $form) {
            return null;
        }

        return [
            'formPath' => route('registrationPage', [
                'registrationForm' => $form->path,
                'lang' => app()->getLocale(),
            ]),
            'institutionId' => $institution->id,
            'institutionName' => $institution->name,
        ];
    }
}
