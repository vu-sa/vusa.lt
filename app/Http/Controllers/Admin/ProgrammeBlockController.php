<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgrammeBlock;
use App\Services\ModelAuthorizer as Authorizer;

class ProgrammeBlockController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammeBlock $programmeBlock)
    {
        $programmeBlock->delete();

        return back()->with('success', 'Programme block deleted.');
    }
}

