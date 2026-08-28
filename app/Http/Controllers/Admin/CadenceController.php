<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Cadences\StoreCadenceRequest;
use App\Http\Requests\Cadences\UpdateCadenceRequest;
use App\Http\Requests\Cadences\UpdateCadenceSettingsRequest;
use App\Models\Cadence;
use App\Models\Meeting;
use App\Settings\CadenceSettings;
use App\Settings\SettingsSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

/**
 * Term boundaries. Kept out of {@see SettingsController} because this is a five-action
 * CRUD surface rather than the single edit/update pair every other settings group has.
 *
 * The settings screen owns the global ladder; institution overrides are created from the
 * institution form and are only listed here.
 */
class CadenceController extends AdminController
{
    /** @var list<string> */
    private const array WRITABLE = [
        'institution_id', 'start_meeting_id', 'end_meeting_id', 'start_date', 'end_date',
    ];

    /**
     * The payload the settings screen and the institution form both read.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function payload(?string $institutionId = null, bool $globalOnly = false): array
    {
        return Cadence::query()
            ->with([
                'institution:id,name,alias',
                'startMeeting:id,title,start_time',
                'startMeeting.institutions:id,name',
                'endMeeting:id,title,start_time',
                'endMeeting.institutions:id,name',
            ])
            ->when($globalOnly, fn ($query) => $query->globalLadder())
            ->when($institutionId !== null, fn ($query) => $query->forInstitution($institutionId))
            ->orderByRaw('institution_id IS NOT NULL')
            ->orderBy('institution_id')
            ->orderBy('start_date')
            ->get()
            ->map(fn (Cadence $cadence) => [
                'id' => $cadence->id,
                'institution_id' => $cadence->institution_id,
                'institution_name' => $cadence->institution?->name,
                'start_date' => $cadence->start_date->toDateString(),
                'end_date' => $cadence->end_date->toDateString(),
                'start_meeting' => self::anchorPayload($cadence->startMeeting),
                'end_meeting' => self::anchorPayload($cadence->endMeeting),
                'label' => $cadence->label,
            ])
            ->all();
    }

    /**
     * Enough to name the sitting a boundary came from and link back to it. The institution
     * comes along because a term may open at another body's sitting — the form only labels
     * the anchor when that institution is not the one owning the term.
     *
     * @return array{id: string, title: string|null, start_time: string, institution_id: string|null, institution_name: string|null}|null
     */
    private static function anchorPayload(?Meeting $meeting): ?array
    {
        if ($meeting === null) {
            return null;
        }

        $institution = $meeting->institutions->first();

        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'start_time' => $meeting->start_time->toIso8601String(),
            'institution_id' => $institution?->id,
            'institution_name' => $institution?->name,
        ];
    }

    public function index(CadenceSettings $cadenceSettings, SettingsSettings $settingsSettings): InertiaResponse
    {
        abort_if(
            ! $settingsSettings->canUserManageSettings(request()->user()),
            403,
            __('settings.messages.unauthorized'),
        );

        return $this->inertiaResponse('Admin/Settings/EditCadenceSettings', [
            'cadences' => self::payload(),
            'settings' => [
                'default_start_month_day' => $cadenceSettings->default_start_month_day,
                'default_end_month_day' => $cadenceSettings->default_end_month_day,
            ],
        ]);
    }

    public function updateDefaults(
        UpdateCadenceSettingsRequest $request,
        CadenceSettings $cadenceSettings,
    ): RedirectResponse {
        $validated = $request->validated();

        $cadenceSettings->default_start_month_day = $validated['default_start_month_day'];
        $cadenceSettings->default_end_month_day = $validated['default_end_month_day'];
        $cadenceSettings->save();

        return back()->with('success', __('settings.messages.updated'));
    }

    public function store(StoreCadenceRequest $request): RedirectResponse
    {
        Cadence::create($request->safe()->only(self::WRITABLE));

        return back()->with('success', $this->entityMessage('created', 'cadence'));
    }

    public function update(UpdateCadenceRequest $request, Cadence $cadence): RedirectResponse
    {
        $cadence->fill($request->safe()->only(self::WRITABLE))->save();

        return back()->with('success', $this->entityMessage('updated', 'cadence'));
    }

    public function destroy(Cadence $cadence): RedirectResponse
    {
        $this->authorize('delete', $cadence);

        $cadence->delete();

        return back()->with('success', $this->entityMessage('deleted', 'cadence'));
    }
}
