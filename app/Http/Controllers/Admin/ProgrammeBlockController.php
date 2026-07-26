<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Traits\AuthorizesProgrammes;
use App\Models\ProgrammeBlock;
use App\Services\ModelAuthorizer as Authorizer;

class ProgrammeBlockController extends AdminController
{
    use AuthorizesProgrammes;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgrammeBlock $programmeBlock)
    {
        $this->authorizeProgrammeMutation($programmeBlock->owningProgramme());

        $programmeBlock->delete();

        return back()->with('success', 'Programme block deleted.');
    }
}
