<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doing;
use App\Http\Controllers\Controller as Controller;
use App\Models\Question;
use App\Models\User;
use App\Services\SharepointAppGraph;
use Illuminate\Http\Request;
use Microsoft\Graph\Model;
use Inertia\Inertia;

class QuestionDoingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function index(Question $question)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function create(Question $question)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Question $question)
    {
        $request->validate([
            'title' => 'required',
            'type_id' => 'required',
        ]);

        $doing = Doing::create
            ($request->only('title') + ['question_id' => $question->id, 'status' => $request->type_id, 'date' => now()]);
        
        $doing->types()->sync($request->type_id);

        return redirect()->route('questions.doings.show', [$question, $doing])->with('success', 'Veiksmas sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function show(Question $question, Doing $doing)
    {
        $sharepointFiles = [];
        
        if ($doing->documents->count() > 0) {
            $graph = new SharepointAppGraph();
        
            $drive = $graph->getDriveBySite(config('filesystems.sharepoint.site_id'));
            // $driveChildren = $graph->getDriveChildren($drive->getId());

            // for test get only the first one
            $driveItems = $graph->getDriveItemsByID(($doing->documents), $drive->getId());

            $sharepointFiles = collect($driveItems)->map(function (Model\DriveItem $item) {
                return [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'webUrl' => $item->getWebUrl(),
                    'createdDateTime' => $item->getCreatedDateTime(),
                    'lastModifiedDateTime' => $item->getLastModifiedDateTime(),
                    'size' => $item->getSize(),
                    'file' => $item->getFile(),
                    'type' => $item->getListItem()->getFields()->getProperties()['Type'] ?? null,
                    'keywords' => $item->getListItem()->getFields()->getProperties()['Keywords'] ?? null,
                    // get date +3 hours and format YYYY-MM-DD
                    'date' => ($item->getListItem()->getFields()->getProperties()['Date'] ?? null) ? date('Y-m-d', strtotime($item->getListItem()->getFields()->getProperties()['Date'] . ' +3 hours')) : null,
                ];
            });
        }

        return Inertia::render('Admin/Questions/ShowDoing', [
            'question' => $question->load('institution'),
            'doing' => [
                    ...$doing->toArray(),
                    'activities' => $doing->activities->map(function ($activity) {
                        return [
                            ...$activity->toArray(),
                            // 'date' => $activity->date->format('Y-m-d'),
                            'causer' => $activity->causer
                        ];
                    }),
                ],
            'documents' => $sharepointFiles,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function edit(Question $question, Doing $doing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Question $question, Doing $doing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Question  $question
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function destroy(Question $question, Doing $doing)
    {
        //
    }
}
