<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\InstitutionLinks\StoreInstitutionTypeLinkRequest;
use App\Http\Requests\InstitutionLinks\UpdateInstitutionTypeLinkRequest;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

/**
 * Links between institution types, edited from either type's record.
 */
class InstitutionTypeLinkController extends AdminController
{
    #[Authorize('create', InstitutionTypeLink::class)]
    public function store(StoreInstitutionTypeLinkRequest $request, InstitutionType $type): RedirectResponse
    {
        InstitutionTypeLink::create($request->linkAttributes());

        return back()->with('success', $this->entityMessage('created', 'relationship'));
    }

    #[Authorize('update', 'institutionTypeLink')]
    public function update(UpdateInstitutionTypeLinkRequest $request, InstitutionTypeLink $institutionTypeLink): RedirectResponse
    {
        $institutionTypeLink->update($request->safe()->only(['kind', 'mutual']));

        return back()->with('success', $this->entityMessage('updated', 'relationship'));
    }

    #[Authorize('delete', 'institutionTypeLink')]
    public function destroy(InstitutionTypeLink $institutionTypeLink): RedirectResponse
    {
        $institutionTypeLink->delete();

        return back()->with('success', $this->entityMessage('deleted', 'relationship'));
    }
}
