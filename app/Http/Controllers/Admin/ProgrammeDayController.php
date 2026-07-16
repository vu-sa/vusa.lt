<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Traits\AuthorizesProgrammes;
use App\Models\ProgrammeDay;
use App\Services\ModelAuthorizer as Authorizer;

class ProgrammeDayController extends AdminController
{
    use AuthorizesProgrammes;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammeDay $programmeDay)
    {
        $this->authorizeProgrammeMutation($programmeDay->owningProgramme());

        $programmeDay->delete();

        return back()->with('success', 'Programme day deleted.');
    }
}
