<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Cadences\StoreCadenceRequest;
use App\Http\Requests\Cadences\UpdateCadenceRequest;
use App\Http\Requests\Cadences\UpdateCadenceSettingsRequest;
use App\Models\Cadence;
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
    /**
     * The payload the settings screen and the institution form both read.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function payload(?string $institutionId = null, bool $globalOnly = false): array
    {
        return Cadence::query()
            ->with('institution:id,name,alias')
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
                'label' => $cadence->label,
            ])
            ->all();
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
        Cadence::create($request->safe()->only(['institution_id', 'start_date', 'end_date']));

        return back()->with('success', $this->entityMessage('created', 'cadence'));
    }

    public function update(UpdateCadenceRequest $request, Cadence $cadence): RedirectResponse
    {
        $cadence->fill($request->safe()->only(['institution_id', 'start_date', 'end_date']))->save();

        return back()->with('success', $this->entityMessage('updated', 'cadence'));
    }

    public function destroy(Cadence $cadence): RedirectResponse
    {
        $this->authorize('delete', $cadence);

        $cadence->delete();

        return back()->with('success', $this->entityMessage('deleted', 'cadence'));
    }
}
