<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\InstitutionLinks\StoreInstitutionLinkRequest;
use App\Http\Requests\InstitutionLinks\UpdateInstitutionLinkRequest;
use App\Models\Institution;
use App\Models\InstitutionLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

/**
 * Direct links between two institutions, edited from either institution's record.
 */
class InstitutionLinkController extends AdminController
{
    #[Authorize('create', InstitutionLink::class)]
    public function store(StoreInstitutionLinkRequest $request, Institution $institution): RedirectResponse
    {
        InstitutionLink::create($request->linkAttributes());

        return back()->with('success', $this->entityMessage('created', 'relationship'));
    }

    #[Authorize('update', 'institutionLink')]
    public function update(UpdateInstitutionLinkRequest $request, InstitutionLink $institutionLink): RedirectResponse
    {
        $institutionLink->update($request->safe()->only(['kind', 'mutual']));

        return back()->with('success', $this->entityMessage('updated', 'relationship'));
    }

    #[Authorize('delete', 'institutionLink')]
    public function destroy(InstitutionLink $institutionLink): RedirectResponse
    {
        $institutionLink->delete();

        return back()->with('success', $this->entityMessage('deleted', 'relationship'));
    }
}
