<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ProgrammeDay;
use App\Services\ModelAuthorizer as Authorizer;

class ProgrammeDayController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammeDay $programmeDay)
    {
        $programmeDay->delete();

        return back()->with('success', 'Programme day deleted.');
    }
}
