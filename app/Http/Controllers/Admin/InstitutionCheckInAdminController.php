<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use Illuminate\Http\Request;

class InstitutionCheckInAdminController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', InstitutionCheckIn::class);

        $query = InstitutionCheckIn::query()
            ->with(['institution:id,name', 'user:id,name'])
            ->when($request->filled('institution_id'), fn($q) => $q->where('institution_id', $request->string('institution_id')))
            ->orderByDesc('created_at');

        return inertia('Admin/CheckIns/Index', [
            'filters' => $request->only(['institution_id']),
            'checkIns' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function destroyActiveCheckIns(Institution $institution)
    {
        $checkIns = $institution->activeCheckIns;

        $this->authorize('delete', $checkIns->first());

        ## Delete all active check-ins
        foreach ($checkIns as $checkIn) {
            $checkIn->delete();
        }

        return redirect()->back()->with('success', __('Active check-ins deleted successfully'));
    }
}
