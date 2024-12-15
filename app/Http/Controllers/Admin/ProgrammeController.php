<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProgrammeRequest;
use App\Models\Programme;
use App\Models\ProgrammeDay;
use App\Models\ProgrammePart;
use App\Models\ProgrammeSection;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgrammeController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Programme $programme)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Programme $programme)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProgrammeRequest $request, Programme $programme)
    {
        $validatedData = $request->validated();

        DB::transaction(function () use ($validatedData, $programme) {
            foreach ($validatedData['days'] as $dayIndex => $dayData) {
                $day = ProgrammeDay::query()->findOrNew($dayData['id']);

                $day->title = $dayData['title'];
                $day->start_time = $dayData['start_time'];
                $day->order = $dayIndex;

                // Associate day with programme
                $programme->days()->save($day);

                foreach ($dayData['elements'] as $elementIndex => $elementData) {
                    if ($elementData['type'] === 'section') {
                        /*        $section = ProgrammeSection::query()->findOrNew($elementData['id']);*/
                        /**/
                        /*        $section->title = $elementData['title'];*/
                        /*        $section->description = $elementData['description'] ?? null;*/
                        /*        $section->duration = $elementData['duration'];*/
                        /*        $section->start_time = $elementData['start_time'];*/
                        /**/
                        /*        $section->save();*/
                        /**/
                        /*        // Associate section with day*/
                        /*        $day->sections()->syncWithoutDetaching([$section->id]);*/
                        /**/
                        /*        foreach ($elementData['blocks'] as $blockIndex => $blockData) {*/
                        /*            $block = $section->elementable->blocks()->updateOrCreate(*/
                        /*                ['id' => $blockData['id'] ?? null],*/
                        /*                [*/
                        /*                    'programme_section_id' => $section->elementable->id,*/
                        /*                    'title' => $blockData['title'],*/
                        /*                    'description' => $blockData['description'] ?? null,*/
                        /*                    'order' => $blockIndex,*/
                        /*                ]*/
                        /*            );*/
                        /**/
                        /*            foreach ($blockData['parts'] as $partIndex => $partData) {*/
                        /*                $block->parts()->updateOrCreate(*/
                        /*                    ['id' => $partData['id'] ?? null],*/
                        /*                    [*/
                        /*                        'title' => $partData['title'],*/
                        /*                        'description' => $partData['description'] ?? null,*/
                        /*                        'duration' => $partData['duration'],*/
                        /*                        'start_time' => $partData['start_time'],*/
                        /*                        'order' => $partIndex,*/
                        /*                    ]*/
                        /*                );*/
                        /*            }*/
                        /*        }*/
                    } elseif ($elementData['type'] === 'part') {
                        $part = ProgrammePart::query()->findOrNew($elementData['id']);

                        $part->title = $elementData['title'];
                        $part->description = $elementData['description'] ?? null;
                        $part->duration = $elementData['duration'];

                        // Check if part is already associated with day
                        if (! $day->parts->contains('id', $part->id)) {
                            $day->parts()->save($part, ['order' => $elementIndex]);
                        } else {
                            $day->parts()->updateExistingPivot($part->id, ['order' => $elementIndex]);
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Programme updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Programme $programme)
    {
        //
    }
}
