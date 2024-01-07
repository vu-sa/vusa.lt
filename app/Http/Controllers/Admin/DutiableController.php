<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DutiableController extends LaravelResourceController
{
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Dutiable $dutiable)
    {
        $this->authorize('update', [Duty::class, $dutiable->duty, $this->authorizer]);

        return Inertia::render('Admin/People/EditDutiable', [
            'dutiable' => $dutiable->load('duty', 'dutiable'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * TODO: this will not work for contacts
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Dutiable $dutiable, Request $request)
    {
        $this->authorize('update', [Duty::class, $dutiable->duty, $this->authorizer]);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'extra_attributes' => 'nullable|array',
        ]);

        $dutiable->update($validated);

        return back()->with('success', 'Pareigybės laikotarpis sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dutiable $dutiable)
    {
        $this->authorize('delete', [Dutiable::class, $dutiable, $this->authorizer]);

        $user = $dutiable->dutiable;

        $dutiable->delete();

        return redirect()->route('users.edit', $user)->with('success', 'Pareigybės laikotarpis sėkmingai ištrintas!');
    }
}
