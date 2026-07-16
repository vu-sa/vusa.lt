<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\BelongsToProgramme;
use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateProgrammeRequest;
use App\Models\Programme;
use App\Models\ProgrammeBlock;
use App\Models\ProgrammeDay;
use App\Models\ProgrammePart;
use App\Models\ProgrammeSection;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgrammeController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Update the specified resource in storage.
     *
     * Authorization is handled by {@see UpdateProgrammeRequest::authorize()},
     * which delegates to the policy of the training that owns this programme.
     */
    public function update(UpdateProgrammeRequest $request, Programme $programme)
    {
        $validatedData = $request->validated();

        DB::transaction(function () use ($validatedData, $programme) {
            foreach ($validatedData['days'] as $dayIndex => $dayData) {
                $day = ProgrammeDay::query()->findOrNew($dayData['id']);
                $this->assertBelongsToProgramme($programme, $day);

                $day->title = $dayData['title'];
                $day->start_time = $dayData['start_time'];
                $day->order = $dayIndex;

                // Associate day with programme
                $programme->days()->save($day);

                foreach ($dayData['elements'] as $elementIndex => $elementData) {
                    if ($elementData['type'] === 'section') {
                        $section = ProgrammeSection::query()->findOrNew($elementData['id']);
                        $this->assertBelongsToProgramme($programme, $section);

                        $section->title = $elementData['title'];
                        $section->duration = $elementData['duration'];

                        $section->save();

                        // Check if section is already associated with day
                        if (! $day->sections->contains('id', $section->id)) {
                            $day->sections()->save($section, ['order' => $elementIndex]);
                        } else {
                            $day->sections()->updateExistingPivot($section->id, ['order' => $elementIndex]);
                        }

                        foreach ($elementData['blocks'] as $blockData) {
                            $block = ProgrammeBlock::query()->findOrNew($blockData['id']);

                            // A block is owned by its section outright, so the
                            // stricter parentage check applies.
                            abort_if(
                                $block->exists && $block->programme_section_id !== $section->id,
                                403,
                                'This block belongs to another section.'
                            );

                            $block->title = $blockData['title'];
                            $block->description = $blockData['description'] ?? null;

                            $block->programme_section_id = $section->id;

                            $block->save();

                            foreach ($blockData['parts'] as $partIndex => $partData) {
                                $part = ProgrammePart::query()->findOrNew($partData['id']);
                                $this->assertBelongsToProgramme($programme, $part);

                                $part->title = $partData['title'];
                                $part->description = $partData['description'] ?? null;
                                $part->duration = $partData['duration'];
                                $part->instructor = $partData['instructor'] ?? null;

                                $part->save();

                                // Check if part is already associated with block
                                if (! $block->parts->contains('id', $part->id)) {
                                    $block->parts()->save($part, ['order' => $partIndex]);
                                } else {
                                    $block->parts()->updateExistingPivot($part->id, ['order' => $partIndex]);
                                }
                            }
                        }
                    } elseif ($elementData['type'] === 'part') {
                        $part = ProgrammePart::query()->findOrNew($elementData['id']);
                        $this->assertBelongsToProgramme($programme, $part);

                        $part->title = $elementData['title'];
                        $part->description = $elementData['description'] ?? null;
                        $part->duration = $elementData['duration'];
                        $part->instructor = $elementData['instructor'] ?? null;

                        // Check if part is already associated with day
                        if (! $day->parts->contains('id', $part->id)) {
                            $day->parts()->save($part, ['order' => $elementIndex]);
                        } else {
                            $day->parts()->updateExistingPivot($part->id, ['order' => $elementIndex]);

                            $part->save();
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Programme updated successfully');
    }

    /**
     * Refuse to pull an existing day, section or part out of another programme
     * and into this one. `findOrNew` happily resolves any id in the payload, so
     * elements that already exist must be proven to belong here. Elements that
     * are new (or not yet attached to anything) have no owner to violate.
     */
    private function assertBelongsToProgramme(Programme $programme, Model&BelongsToProgramme $element): void
    {
        if (! $element->exists) {
            return;
        }

        $owner = $element->owningProgramme();

        abort_if(
            $owner !== null && $owner->id !== $programme->id,
            403,
            'This element belongs to another programme.'
        );
    }
}
