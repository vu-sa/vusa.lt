<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\DutyInstitution;
use App\Models\Relationshipable;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // load user duty institutions
        $user = User::find(auth()->user()->id);
        $duties = $user->duties;

        $dutyInstitutions = $duties->pluck('institution')->flatten()->unique();

        // convert to eloquent collection
        $dutyInstitutions = new EloquentCollection($dutyInstitutions);

        $dutyInstitutions->load('users:users.id,profile_photo_path');

        return Inertia::render('Admin/ShowDashboard', [
            'dutyInstitutions' => $dutyInstitutions->map(function ($institution) {
                return [...$institution->toArray(), 'lastMeetingDoing' => $institution->lastMeetingDoing()];
            }),
            'duties' => $duties->load('institution')
        ]);
    }

    public function userSettings()
    {
        $user = User::find(auth()->user()->id);
        
        return Inertia::render('Admin/ShowUserSettings', [
            'roles' => $user->getRoleNames(),
        ]);
    }

    public function userTasks()
    {
        $user = User::find(auth()->user()->id);

        $tasks = $user->tasks->load('taskable', 'users');

        return Inertia::render('Admin/ShowTasks', [
            'tasks' => $tasks
        ]);
    }

    public function dutyInstitutionGraph() {
       
        // return dutyInstitutions with user count
        $dutyInstitutions = DutyInstitution::withCount('users')->get();

        // get relationships for duty institutions
        $dutyInstitutionRelationships = DB::table('relationshipables')->where('relationshipable_type', DutyInstitution::class)->get();

        $typeRelationships = $this->getTypeRelationships();

        $dutyInstitutionRelationships = $dutyInstitutionRelationships->merge($typeRelationships);

        return Inertia::render('Admin/ShowDutyInstitutionGraph', [
            'dutyInstitutions' => $dutyInstitutions,
            'dutyInstitutionRelationships' => $dutyInstitutionRelationships,
        ]);
    }

    protected function getTypeRelationships()
    {
        $relationships = Relationshipable::where('relationshipable_type', Type::class)->get()->map(function ($relationshipable) {
            return $relationshipable->getRelatedModelsFromGivenType(DutyInstitution::class);
        });

        return collect($relationships)->flatten(1);
    }
}
