<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDutiableRequest;
use App\Models\Pivots\Dutiable;
use Inertia\Inertia;
use App\Services\ModelAuthorizer as Authorizer;

class DutiableController extends Controller
{
   public function __construct(public Authorizer $authorizer) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Dutiable $dutiable)
    {
        $this->authorize('update', $dutiable->duty);

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
    public function update(Dutiable $dutiable, UpdateDutiableRequest $request)
    {
        $dutiable->fill($request->validated());

        $dutiable->save();

        return back()->with('success', 'Pareigybės laikotarpis sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dutiable $dutiable)
    {
        $this->authorize('delete', $dutiable->duty);

        $user = $dutiable->dutiable;

        $dutiable->delete();

        return redirect()->route('users.edit', $user)->with('success', 'Pareigybės laikotarpis sėkmingai ištrintas!');
    }
}
