<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateDutiableRequest;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Services\ModelAuthorizer as Authorizer;

class DutiableController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dutiable $dutiable)
    {
        $this->authorize('manageDutiable', $dutiable);

        return $this->inertiaResponse('Admin/People/EditDutiable', [
            'dutiable' => $dutiable->load('duty', 'dutiable', 'viaDutiable.duty')->toFullArray(),
            'studyPrograms' => StudyProgram::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * TODO: this will not work for contacts
     */
    public function update(Dutiable $dutiable, UpdateDutiableRequest $request)
    {
        $this->authorize('manageDutiable', $dutiable);

        $data = $request->validated();

        // Derived (ex-officio) rows have their dates mirrored from the source;
        // do not allow manual overrides.
        if (! is_null($dutiable->via_dutiable_id)) {
            unset($data['start_date'], $data['end_date']);
        }

        $dutiable->fill($data)->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pareigybės el. paštas sėkmingai atnaujintas!',
            ]);
        }

        return back()->with('success', 'Pareigybės laikotarpis sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dutiable $dutiable)
    {
        $this->authorize('manageDutiable', $dutiable);

        $user = $dutiable->dutiable;

        $dutiable->delete();

        return redirect()->route('users.edit', $user)->with('success', 'Pareigybės laikotarpis sėkmingai ištrintas!');
    }
}
