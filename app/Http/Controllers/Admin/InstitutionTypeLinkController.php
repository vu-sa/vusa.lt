<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\InstitutionLinks\StoreInstitutionTypeLinkRequest;
use App\Http\Requests\InstitutionLinks\UpdateInstitutionTypeLinkRequest;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeLink;
use Illuminate\Http\RedirectResponse;

/**
 * Links between institution types, edited from either type's record.
 */
class InstitutionTypeLinkController extends AdminController
{
    public function store(StoreInstitutionTypeLinkRequest $request, InstitutionType $type): RedirectResponse
    {
        InstitutionTypeLink::create($request->linkAttributes());

        return back()->with('success', $this->entityMessage('created', 'relationship'));
    }

    public function update(UpdateInstitutionTypeLinkRequest $request, InstitutionTypeLink $institutionTypeLink): RedirectResponse
    {
        $institutionTypeLink->update($request->safe()->only(['kind', 'mutual']));

        return back()->with('success', $this->entityMessage('updated', 'relationship'));
    }

    public function destroy(InstitutionTypeLink $institutionTypeLink): RedirectResponse
    {
        $this->handleAuthorization('delete', $institutionTypeLink);

        $institutionTypeLink->delete();

        return back()->with('success', $this->entityMessage('deleted', 'relationship'));
    }
}
