<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\UpdateDutiableRequest;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DutiableController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /*public function index(Request $request)*/
    /*{*/
    /*    $this->authorize('viewAny', [Dutiable::class, $this->authorizer]);*/
    /**/
    /*    $indexer = new ModelIndexer(new Dutiable, $request, $this->authorizer);*/
    /**/
    /*    $dutiables = $indexer*/
    /*        ->setEloquentQuery()*/
    /*        ->filterAllColumns()*/
    /*        ->sortAllColumns()*/
    /*        ->builder->with('duty', 'dutiable', 'tenants')->paginate(20);*/
    /**/
    /*    return Inertia::render('Admin/People/IndexDutiable', [*/
    /*        'dutiables' => $dutiables,*/
    /*    ]);*/
    /*}*/

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
        $this->authorize('delete', [Duty::class, $dutiable->duty, $this->authorizer]);

        $user = $dutiable->dutiable;

        $dutiable->delete();

        return redirect()->route('users.edit', $user)->with('success', 'Pareigybės laikotarpis sėkmingai ištrintas!');
    }
}
