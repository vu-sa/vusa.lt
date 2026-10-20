<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ForgetUserAccess;
use App\Enums\ResponsibilityPurpose;
use App\Enums\SystemDutyType;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Requests\UpdateAtstovavimasSettingsRequest;
use App\Http\Requests\UpdateDocumentSettingsRequest;
use App\Http\Requests\UpdateFormSettingsRequest;
use App\Http\Requests\UpdateMeetingSettingsRequest;
use App\Http\Requests\UpdateSiteSettingsRequest;
use App\Http\Requests\UpdateSystemPurposesSettingsRequest;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\Form;
use App\Models\InstitutionType;
use App\Models\Page;
use App\Models\PublicInstitution;
use App\Models\PublicMeeting;
use App\Models\Role;
use App\Models\User;
use App\Services\RepMetricReportService;
use App\Services\ResponsibilityResolver;
use App\Services\SystemDutyTypes;
use App\Services\Typesense\DocumentRecommendations;
use App\Settings\AtstovavimasSettings;
use App\Settings\DocumentSettings;
use App\Settings\DutyTypeSettings;
use App\Settings\FormSettings;
use App\Settings\MeetingSettings;
use App\Settings\RepMetricSettings;
use App\Settings\SettingsSettings;
use App\Settings\SiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Response as InertiaResponse;

class SettingsController extends AdminController
{
    /**
     * Writing needs `settings.update.*`.
     */
    private function authorizeSettingsAccess(SettingsSettings $settings): void
    {
        abort_if(! $settings->canUserManageSettings(request()->user()), 403, __('settings.messages.unauthorized'));
    }

    /**
     * Opening a page needs only `settings.read.*`; `canEdit` tells it to hide its save controls.
     *
     * @param  array<string, mixed>  $props
     */
    private function settingsPage(SettingsSettings $settings, string $component, array $props = []): InertiaResponse
    {
        $user = request()->user();

        abort_if(! $settings->canUserViewSettings($user), 403, __('settings.messages.unauthorized'));

        return $this->inertiaResponse($component, [...$props, 'canEdit' => $settings->canUserManageSettings($user)]);
    }

    /**
     * Show settings index page.
     */
    public function index(SettingsSettings $settings)
    {
        return $this->settingsPage($settings, 'Admin/Settings/IndexSettings');
    }

    /**
     * Show form settings.
     */
    public function editFormSettings(FormSettings $formSettings, SettingsSettings $settingsSettings)
    {
        return $this->settingsPage($settingsSettings, 'Admin/Settings/EditFormSettings', [
            'member_registration_form_id' => $formSettings->member_registration_form_id,
            'student_rep_registration_form_id' => $formSettings->student_rep_registration_form_id,
            'student_rep_institution_type_ids' => $formSettings->getStudentRepInstitutionTypeIds()->toArray(),
            'forms' => Form::all(['id', 'name']),
            'memberRegistration' => $this->memberRegistrationCoverage(),
            'institution_types' => InstitutionType::query()

                ->get(['id', 'title', 'slug'])
                ->map->toArray(),
        ]);
    }

    /**
     * Update form settings.
     */
    public function updateFormSettings(UpdateFormSettingsRequest $request, FormSettings $formSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        $formSettings->member_registration_form_id = $request->member_registration_form_id;
        $formSettings->student_rep_registration_form_id = $request->student_rep_registration_form_id;
        $formSettings->setStudentRepInstitutionTypeIds($request->input('student_rep_institution_type_ids', []));

        $formSettings->save();

        // The sidebar links to these forms, so every user's cached ids are now stale.
        User::query()->pluck('id')->each(
            fn ($userId) => Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($userId))
        );

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    /**
     * Show meeting settings.
     */
    public function editMeetingSettings(MeetingSettings $meetingSettings, SettingsSettings $settingsSettings)
    {
        return $this->settingsPage($settingsSettings, 'Admin/Settings/EditMeetingSettings', [
            'selected_type_ids' => $meetingSettings->getPublicMeetingInstitutionTypeIds()->toArray(),
            'excluded_type_ids' => $meetingSettings->getExcludedInstitutionTypeIds()->toArray(),
            'available_types' => InstitutionType::query()

                ->get(['id', 'title', 'slug'])
                ->map->toArray(),
        ]);
    }

    /**
     * Update meeting settings.
     */
    public function updateMeetingSettings(UpdateMeetingSettingsRequest $request, MeetingSettings $meetingSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        $meetingSettings->setPublicMeetingInstitutionTypeIds($request->input('type_ids', []));
        $meetingSettings->setExcludedInstitutionTypeIds($request->input('excluded_type_ids', []));
        $meetingSettings->save();

        // Reindex public meetings and institutions since visibility criteria changed
        // Flush first to remove meetings/institutions that no longer match, then reimport
        dispatch(function (): void {
            PublicMeeting::removeAllFromSearch();
            PublicMeeting::makeAllSearchable();

            PublicInstitution::removeAllFromSearch();
            PublicInstitution::makeAllSearchable();
        })->afterResponse();

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    public function editSystemPurposes(SettingsSettings $settingsSettings, DutyTypeSettings $dutyTypeSettings, SystemDutyTypes $systemDutyTypes)
    {
        return $this->settingsPage($settingsSettings, 'Admin/Settings/EditSystemPurposesSettings', [
            'purposes' => collect(ResponsibilityPurpose::cases())->map(fn (ResponsibilityPurpose $purpose): array => [
                'key' => $purpose->value,
                'label' => (string) __($purpose->labelKey()),
                'drives' => (string) __($purpose->drivesKey()),
                'responsibility_id' => Role::forPurpose($purpose)?->id,
            ])->all(),
            'responsibilities' => Role::query()->exceptSuperAdmin()->get()
                ->map(fn (Role $role): array => ['id' => $role->id, 'title' => $role->displayTitle()])
                ->sortBy('title')
                ->values(),
            'dutyTypes' => collect(SystemDutyType::cases())->map(fn (SystemDutyType $part): array => [
                'key' => $part->value,
                'label' => (string) __($part->labelKey()),
                'drives' => (string) __($part->drivesKey()),
                'type_id' => $dutyTypeSettings->{$part->settingKey()},
                'effective_type_id' => $systemDutyTypes->id($part),
                'fallback_slug' => $part->fallbackSlug(),
            ])->all(),
            'dutyTypeOptions' => DutyType::query()->get(['id', 'title'])
                ->map(fn (DutyType $type): array => ['id' => (int) $type->id, 'title' => (string) $type->getTranslation('title', app()->getLocale())])
                ->sortBy('title')
                ->values(),
        ]);
    }

    public function updateSystemPurposes(UpdateSystemPurposesSettingsRequest $request, DutyTypeSettings $dutyTypeSettings, SystemDutyTypes $systemDutyTypes)
    {
        $affectedRoles = collect();

        DB::transaction(function () use ($request, &$affectedRoles): void {
            foreach (ResponsibilityPurpose::cases() as $purpose) {
                $roleId = $request->validated("purposes.{$purpose->value}");

                Role::query()->where('purpose', $purpose)->whereKeyNot($roleId)->get()
                    ->each(function (Role $role) use (&$affectedRoles): void {
                        $affectedRoles->push($role);
                        $role->update(['purpose' => null]);
                    });

                if ($roleId !== null) {
                    $role = Role::query()->whereKey($roleId)->firstOrFail();
                    $affectedRoles->push($role);
                    $role->update(['purpose' => $purpose]);
                }
            }
        });

        ForgetUserAccess::execute(
            $affectedRoles->unique('id')
                ->flatMap(fn (Role $role) => $role->usersThroughDuties()->pluck('users.id')->merge($role->users()->pluck('users.id')))
                ->map(strval(...))
                ->unique()
                ->values(),
        );

        foreach (SystemDutyType::cases() as $part) {
            $typeId = $request->validated("duty_types.{$part->value}");
            $dutyTypeSettings->{$part->settingKey()} = $typeId === null ? null : (int) $typeId;
        }

        $dutyTypeSettings->save();
        $systemDutyTypes->flush();

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    /**
     * Show atstovavimas settings.
     */
    public function editAtstovavimasSettings(AtstovavimasSettings $atstovavimasSettings, SettingsSettings $settingsSettings)
    {
        return $this->settingsPage($settingsSettings, 'Admin/Settings/EditAtstovavimasSettings', [
            'baseline_cadence_id' => app(RepMetricSettings::class)->baseline_cadence_id,
            'baseline_cadences' => app(RepMetricReportService::class)->baselineOptions()->get(['id', 'start_date', 'end_date'])->toArray(),
            'student_rep_root_type_id' => $atstovavimasSettings->student_rep_root_type_id,
            'institution_types' => InstitutionType::query()

                ->get(['id', 'title', 'slug'])
                ->map->toArray(),
        ]);
    }

    /**
     * Update atstovavimas settings.
     */
    public function updateAtstovavimasSettings(UpdateAtstovavimasSettingsRequest $request, AtstovavimasSettings $atstovavimasSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        if (array_key_exists('baseline_cadence_id', $request->validated())) {
            $metrics = app(RepMetricSettings::class);
            $metrics->baseline_cadence_id = $request->validated('baseline_cadence_id');
            $metrics->save();
        }

        $oldRootTypeId = $atstovavimasSettings->student_rep_root_type_id;
        $newRootTypeId = $request->validated('student_rep_root_type_id', $oldRootTypeId) ? (int) $request->validated('student_rep_root_type_id', $oldRootTypeId) : null;

        $atstovavimasSettings->student_rep_root_type_id = $newRootTypeId;
        $atstovavimasSettings->save();

        if ($oldRootTypeId !== $newRootTypeId) {
            AtstovavimasSettings::clearStudentRepTypeCache();
        }

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    /**
     * Show document settings.
     */
    public function editDocumentSettings(DocumentSettings $documentSettings, SettingsSettings $settingsSettings)
    {
        return $this->settingsPage($settingsSettings, 'Admin/Settings/EditDocumentSettings', [
            'selected_content_types' => $documentSettings->getImportantContentTypes()->toArray(),
            'recommendations' => $documentSettings->recommendations,
            'selected_documents' => Document::query()->whereIn('id', array_column($documentSettings->recommendations, 'document_id'))
                ->get(['id', 'title', 'status', 'removed_from_sharepoint_at'])->map(fn (Document $document) => ['id' => (string) $document->id, 'title' => $document->title, 'is_active' => $document->isPublished()])->values(),
            'available_content_types' => Document::query()
                ->select('content_type')
                ->whereNotNull('content_type')
                ->distinct()
                ->orderBy('content_type')
                ->pluck('content_type')
                ->values()
                ->toArray(),
        ]);
    }

    /**
     * Update document settings.
     */
    public function updateDocumentSettings(UpdateDocumentSettingsRequest $request, DocumentSettings $documentSettings, SettingsSettings $settingsSettings, DocumentRecommendations $recommendations)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        $documentSettings->setImportantContentTypes($request->input('important_content_types', []));
        $documentSettings->recommendations = collect($request->validated('recommendations'))->map(fn ($recommendation) => [
            'document_id' => (string) $recommendation['document_id'],
            'phrases' => array_values(array_unique(array_filter(array_map(trim(...), $recommendation['phrases'])))),
            'enabled' => (bool) $recommendation['enabled'],
            'show_without_query' => (bool) $recommendation['show_without_query'],
        ])->all();
        $documentSettings->save();
        // The rules are saved either way; a Typesense outage only delays them until `typesense:apply-search-config`.
        rescue(fn () => $recommendations->synchronize());

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    /**
     * Show site-wide settings.
     */
    public function editSiteSettings(SiteSettings $siteSettings, SettingsSettings $settingsSettings)
    {
        $ids = array_filter([$siteSettings->privacy_page_id_lt, $siteSettings->privacy_page_id_en]);

        // Only live pages — the picker searches Typesense, which also excludes them.
        $pages = Page::query()
            ->with('tenant')
            ->where('is_active', true)
            ->whereKey($ids)
            ->get()
            ->keyBy(fn (Page $page) => (string) $page->id);

        $summary = function (?string $id) use ($pages): ?array {
            $page = $id !== null ? $pages->get($id) : null;

            if ($page === null) {
                return null;
            }

            return [
                'id' => (string) $page->id,
                'title' => $page->title,
                'lang' => $page->lang,
                'tenant_shortname' => $page->tenant->shortname,
            ];
        };

        return $this->settingsPage($settingsSettings, 'Admin/Settings/EditSiteSettings', [
            'selectedPages' => [
                'lt' => $summary($siteSettings->privacy_page_id_lt),
                'en' => $summary($siteSettings->privacy_page_id_en),
            ],
        ]);
    }

    /**
     * Update site-wide settings.
     */
    public function updateSiteSettings(UpdateSiteSettingsRequest $request, SiteSettings $siteSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        $validated = $request->validated();

        $siteSettings->privacy_page_id_lt = $validated['privacy_page_id_lt'] ?? null;
        $siteSettings->privacy_page_id_en = $validated['privacy_page_id_en'] ?? null;
        $siteSettings->save();

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    /**
     * Who receives member registrations now that it is a responsibility, and which padaliniai nobody covers.
     *
     * @return array{responsibility: array{id: string, title: string}|null, gaps: list<string>}
     */
    private function memberRegistrationCoverage(): array
    {
        $role = Role::forPurpose(ResponsibilityPurpose::MemberRegistration);

        return [
            'responsibility' => $role ? ['id' => $role->id, 'title' => $role->displayTitle()] : null,
            'gaps' => app(ResponsibilityResolver::class)->gaps(ResponsibilityPurpose::MemberRegistration)
                ->map(fn ($tenant): string => (string) __($tenant->shortname))
                ->values()
                ->all(),
        ];
    }
}
