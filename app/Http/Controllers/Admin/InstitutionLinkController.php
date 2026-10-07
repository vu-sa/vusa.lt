<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\InstitutionLinks\StoreInstitutionLinkRequest;
use App\Http\Requests\InstitutionLinks\UpdateInstitutionLinkRequest;
use App\Models\Institution;
use App\Models\InstitutionLink;
use Illuminate\Http\RedirectResponse;

/**
 * Direct links between two institutions, edited from either institution's record.
 */
class InstitutionLinkController extends AdminController
{
    public function store(StoreInstitutionLinkRequest $request, Institution $institution): RedirectResponse
    {
        InstitutionLink::create($request->linkAttributes());

        return back()->with('success', $this->entityMessage('created', 'relationship'));
    }

    public function update(UpdateInstitutionLinkRequest $request, InstitutionLink $institutionLink): RedirectResponse
    {
        $institutionLink->update($request->safe()->only(['kind', 'mutual']));

        return back()->with('success', $this->entityMessage('updated', 'relationship'));
    }

    public function destroy(InstitutionLink $institutionLink): RedirectResponse
    {
        $this->handleAuthorization('delete', $institutionLink);

        $institutionLink->delete();

        return back()->with('success', $this->entityMessage('deleted', 'relationship'));
    }
}
