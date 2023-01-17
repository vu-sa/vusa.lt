<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\Institution;
use App\Models\User;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // load user duty institutions
        $user = User::with('duties.institution.padalinys', 'duties.institution.users:users.id,users.name,profile_photo_path,phone')->find(auth()->user()->id);
        
        $duties = $user->duties;

        $institutions = $duties->pluck('institution')->flatten()->unique();

        // convert to eloquent collection
        $institutions = new EloquentCollection($institutions);

        return Inertia::render('Admin/ShowDashboard', [
            'institutions' => $institutions,
            'duties' => $duties
        ]);
    }

    public function userSettings()
    {
        $user = User::find(Auth::id());

        $user->load('roles:id,name', 
            'duties:id,name,institution_id', 
            'duties.roles:id,name', 'duties.roles.permissions:id,name',
            'duties.institution:id,padalinys_id', 
            'duties.institution.padalinys:id,shortname');
        
        return Inertia::render('Admin/ShowUserSettings', [
            'user' => $user->toArray(),
        ]);
    }

    public function userTasks()
    {
        $user = User::find(auth()->user()->id);

        $tasks = $user->tasks->load('taskable', 'users');

        return Inertia::render('Admin/ShowTasks', [
            'tasks' => $tasks,
            'institutions' => Inertia::lazy(fn () => Institution::select('id', 'name')->withWhereHas('users:users.id,users.name,profile_photo_path,phone')->get()),
        ]);
    }

    public function institutionGraph() {
       
        // return institutions with user count
        $institutions = Institution::withCount('users')->get();

        return Inertia::render('Admin/ShowInstitutionGraph', [
            'institutions' => $institutions,
            'institutionRelationships' => RelationshipService::getAllRelatedInstitutions(),
        ]);
    }

    public function workspace(Institution $institution) {
        
        // check if institution has id
        if (!is_null($institution->id)) {
            $institution->load('meetings.comments');
        } else {
            $institution = null;
        }

        $user = User::with('institutions')->find(auth()->user()->id);

        return Inertia::render('Admin/ShowWorkspace', 
            [
                'institution' => fn () => $institution,
                'user' => fn () => $user,
            ]
        );
    }
}
