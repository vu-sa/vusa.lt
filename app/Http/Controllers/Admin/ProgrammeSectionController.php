<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Traits\AuthorizesProgrammes;
use App\Models\ProgrammeDay;
use App\Models\ProgrammeSection;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;

class ProgrammeSectionController extends AdminController
{
    use AuthorizesProgrammes;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammeSection $programmeSection)
    {
        $this->authorizeProgrammeMutation($programmeSection->owningProgramme());

        $programmeSection->programmeDays()->detach();
        $programmeSection->delete();

        return back()->with('success', 'Programme section deleted.');
    }

    public function attach(Request $request, ProgrammeSection $programmeSection)
    {
        $day = $this->resolveDay($request);

        // The section must not be dragged out of a programme the user cannot
        // edit and into one they can, so both ends of the move are authorized.
        $this->authorizeCurrentProgrammeIfAttached($programmeSection->owningProgramme());
        $this->authorizeProgrammeMutation($day->owningProgramme());

        $programmeSection->programmeDays()->attach($day->id, ['order' => $request->integer('order', 0)]);

        return back()->with('success', 'Programme section attached to day.');
    }

    public function detach(Request $request, ProgrammeSection $programmeSection)
    {
        $day = $this->resolveDay($request);

        $this->authorizeProgrammeMutation($day->owningProgramme());

        $programmeSection->programmeDays()->detach($day->id);

        return back()->with('success', 'Programme section detached from day.');
    }

    private function resolveDay(Request $request): ProgrammeDay
    {
        $validated = $request->validate([
            'programmeDay' => ['required', 'integer', 'exists:programme_days,id'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        return ProgrammeDay::query()->findOrFail($validated['programmeDay']);
    }
}
