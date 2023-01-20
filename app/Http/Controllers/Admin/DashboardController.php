<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\Institution;
use App\Models\User;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // load user duty institutions
        $user = User::with('duties.institution.padalinys', 'duties.institution.users:users.id,users.name,profile_photo_path,phone')->find(auth()->user()->id);
        
        $duties = $user->duties;

        $institutions = $duties->pluck('institution')->flatten()->unique()->values();

        // convert to eloquent collection
        $institutions = new EloquentCollection($institutions);

        // load institutions with meetings where start_time is in the future
        $institutions->load(['meetings' => function ($query) {
            $query->where('start_time', '>', now())->with('comments', 'tasks', 'files');
        }]);

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
            'taskableInstitutions' => Inertia::lazy(fn () => Institution::select('id', 'name')->withWhereHas('users:users.id,users.name,profile_photo_path,phone')->get()),
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

    public function workspace(Request $request) {
        
        return Inertia::render('Admin/ShowWorkspace', 
            [
                'institution' => fn () => $this->getInstitutionForWorkspace($request),
                // get all institutions where has relationship with auth user
                'userInstitutions' => Institution::whereHas('users', function ($query) {
                    $query->where('users.id', auth()->user()->id);
                })->with('users')->withCount('meetings')->get()
            ]
        );
    }

    protected function getInstitutionForWorkspace(Request $request) {
        $institution = null;
        
        // check if institution has id
        if (!is_null($request->input('institution_id'))) {
            $institution = Institution::with('meetings.comments', 'meetings.tasks', 'meetings.files', 'users')->find($request->input('institution_id'));
        }

        return $institution;
    }
}
