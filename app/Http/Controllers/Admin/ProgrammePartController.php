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
}
