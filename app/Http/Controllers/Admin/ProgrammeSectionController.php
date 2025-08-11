<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ProgrammeSection;
use App\Services\ModelAuthorizer as Authorizer;

class ProgrammeSectionController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammeSection $programmeSection)
    {
        $programmeSection->programmeDays()->detach();
        $programmeSection->delete();

        return back()->with('success', 'Programme section deleted.');
    }

    public function attach(ProgrammeSection $programmeSection)
    {
        $programmeSection->programmeDays()->attach(request('programmeDay'), ['order' => request('order')]);

        return back()->with('success', 'Programme section attached to day.');
    }

    public function detach(ProgrammeSection $programmeSection)
    {
        $programmeSection->programmeDays()->detach(request('programmeDay'));

        return back()->with('success', 'Programme section detached from day.');
    }
}
