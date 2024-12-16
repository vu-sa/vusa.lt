<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgrammePart;
use App\Services\ModelAuthorizer as Authorizer;

class ProgrammePartController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammePart $programmePart)
    {
        $programmePart->programmeDays()->detach();
        $programmePart->delete();

        return back()->with('success', 'Programme part deleted.');
    }

    public function attach(ProgrammePart $programmePart)
    {
        if (request('programmeDay')) {
            $programmePart->programmeDays()->attach(request('programmeDay'), ['order' => request('order')]);

            return back()->with('success', 'Programme part attached to day.');
        } elseif (request('programmeBlock')) {
            $programmePart->programmeBlocks()->attach(request('programmeBlock'));

            return back()->with('success', 'Programme part attached to block.');
        }
    }

    public function detach(ProgrammePart $programmePart)
    {
        if (request('programmeBlock')) {
            $programmePart->programmeBlocks()->detach(request('programmeBlock'));

            return back()->with('success', 'Programme part detached from block.');
        } elseif (request('programmeDay')) {
            $programmePart->programmeDays()->detach(request('programmeDay'));

            return back()->with('success', 'Programme part detached from day.');
        }
    }
}
