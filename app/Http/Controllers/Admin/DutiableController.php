<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateDutiableRequest;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;

class DutiableController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Whether the given dutiable belongs to the currently authenticated user,
     * meaning a change to it could affect their own access.
     */
    private function affectsActingUser(Dutiable $dutiable, Request $request): bool
    {
        // Super admins short-circuit every permission, so duty changes can never
        // lock them out — no need to analyze.
        if ($request->user()->isSuperAdmin()) {
            return false;
        }

        return $dutiable->dutiable_type === User::class
            && (string) $dutiable->dutiable_id === (string) $request->user()->id;
    }

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

        $mutation = fn () => $dutiable->fill($data)->save();

        // Email-only edits (JSON) never change the active period, so they can't
        // affect access; only guard the date-editing (Inertia) path.
        $couldAffectSelf = ! $request->wantsJson() && $this->affectsActingUser($dutiable, $request);

        if ($warning = $this->guardSelfLockout($request->user(), $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

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
    public function destroy(Dutiable $dutiable, Request $request)
    {
        $this->authorize('manageDutiable', $dutiable);

        $user = $dutiable->dutiable;

        $mutation = fn () => $dutiable->delete();

        if ($warning = $this->guardSelfLockout($request->user(), $this->affectsActingUser($dutiable, $request), $request, $mutation)) {
            return $warning;
        }

        return redirect()->route('users.edit', $user)->with('success', 'Pareigybės laikotarpis sėkmingai ištrintas!');
    }
}
