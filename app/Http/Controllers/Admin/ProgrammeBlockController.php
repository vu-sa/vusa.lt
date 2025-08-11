<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ProgrammeBlock;
use App\Services\ModelAuthorizer as Authorizer;

class ProgrammeBlockController extends AdminController
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
