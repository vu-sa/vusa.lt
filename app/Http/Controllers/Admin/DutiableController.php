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
        $this->handleAuthorization('update', $dutiable->duty);

        return $this->inertiaResponse('Admin/People/EditDutiable', [
            'dutiable' => $dutiable->load('duty', 'dutiable')->toFullArray(),
            'studyPrograms' => StudyProgram::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * TODO: this will not work for contacts
     */
    public function update(Dutiable $dutiable, UpdateDutiableRequest $request)
    {
        $dutiable->fill($request->validated());

        $dutiable->save();

        return back()->with('success', 'Pareigybės laikotarpis sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dutiable $dutiable)
    {
        $this->handleAuthorization('delete', $dutiable->duty);

        $user = $dutiable->dutiable;

        $dutiable->delete();

        return redirect()->route('users.edit', $user)->with('success', 'Pareigybės laikotarpis sėkmingai ištrintas!');
    }
}
