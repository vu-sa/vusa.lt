<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Traits\AuthorizesProgrammes;
use App\Models\ProgrammeBlock;
use App\Models\ProgrammeDay;
use App\Models\ProgrammePart;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;

class ProgrammePartController extends AdminController
{
    use AuthorizesProgrammes;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammePart $programmePart)
    {
        $this->authorizeProgrammeMutation($programmePart->owningProgramme());

        $programmePart->programmeDays()->detach();
        $programmePart->delete();

        return back()->with('success', 'Programme part deleted.');
    }

    public function attach(Request $request, ProgrammePart $programmePart)
    {
        $validated = $this->validateTarget($request);

        // The part must not be dragged out of a programme the user cannot edit
        // and into one they can, so both ends of the move are authorized.
        $this->authorizeCurrentProgrammeIfAttached($programmePart->owningProgramme());

        if (isset($validated['programmeDay'])) {
            $day = ProgrammeDay::query()->findOrFail($validated['programmeDay']);
            $this->authorizeProgrammeMutation($day->owningProgramme());

            $programmePart->programmeDays()->attach($day->id, ['order' => $validated['order'] ?? 0]);

            return back()->with('success', 'Programme part attached to day.');
        }

        $block = ProgrammeBlock::query()->findOrFail($validated['programmeBlock']);
        $this->authorizeProgrammeMutation($block->owningProgramme());

        $programmePart->programmeBlocks()->attach($block->id);

        return back()->with('success', 'Programme part attached to block.');
    }

    public function detach(Request $request, ProgrammePart $programmePart)
    {
        $validated = $this->validateTarget($request);

        if (isset($validated['programmeBlock'])) {
            $block = ProgrammeBlock::query()->findOrFail($validated['programmeBlock']);
            $this->authorizeProgrammeMutation($block->owningProgramme());

            $programmePart->programmeBlocks()->detach($block->id);

            return back()->with('success', 'Programme part detached from block.');
        }

        $day = ProgrammeDay::query()->findOrFail($validated['programmeDay']);
        $this->authorizeProgrammeMutation($day->owningProgramme());

        $programmePart->programmeDays()->detach($day->id);

        return back()->with('success', 'Programme part detached from day.');
    }

    /**
     * A part is placed either on a day or inside a block — exactly one target is
     * required, and it must exist.
     *
     * @return array{programmeDay?: int, programmeBlock?: int, order?: int}
     */
    private function validateTarget(Request $request): array
    {
        return $request->validate([
            'programmeDay' => ['required_without:programmeBlock', 'integer', 'exists:programme_days,id'],
            'programmeBlock' => ['required_without:programmeDay', 'integer', 'exists:programme_blocks,id'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
