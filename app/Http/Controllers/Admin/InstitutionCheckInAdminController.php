<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstitutionCheckIn;
use Illuminate\Http\Request;

class InstitutionCheckInAdminController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', InstitutionCheckIn::class);

        $query = InstitutionCheckIn::query()
            ->with(['institution:id,name', 'user:id,name', 'verifications.user:id,name,profile_photo_path'])
            ->when($request->filled('institution_id'), fn($q) => $q->where('institution_id', $request->string('institution_id')))
            ->when($request->filled('state'), fn($q) => $q->where('state', $request->string('state')))
            ->when($request->filled('mode'), fn($q) => $q->where('mode', $request->string('mode')))
            ->orderByDesc('checked_at');

        return inertia('Admin/CheckIns/Index', [
            'filters' => $request->only(['institution_id', 'state', 'mode']),
            'checkIns' => $query->paginate(20)->withQueryString(),
        ]);
    }
}
