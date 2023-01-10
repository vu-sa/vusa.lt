<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModelEnum;
use App\Http\Controllers\Controller as Controller;
use App\Models\Institution;
use App\Models\Pivots\Relationshipable;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // load user duty institutions
        $user = User::with('duties.institution', 'duties.institution.users:users.id,users.name,profile_photo_path')->find(auth()->user()->id);
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
            'duties.roles:id,name', 
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
            'tasks' => $tasks
        ]);
    }

    public function institutionGraph() {
       
        // return institutions with user count
        $institutions = Institution::withCount('users')->get();

        // get relationships for duty institutions
        $institutionRelationships = DB::table('relationshipables')->where('relationshipable_type', Institution::class)->get();

        $typeRelationships = $this->getTypeRelationships();

        $institutionRelationships = $institutionRelationships->merge($typeRelationships);

        return Inertia::render('Admin/ShowInstitutionGraph', [
            'institutions' => $institutions,
            'institutionRelationships' => $institutionRelationships,
        ]);
    }

    protected function getTypeRelationships()
    {
        $relationships = Relationshipable::where('relationshipable_type', Type::class)->get()->map(function ($relationshipable) {
            return $relationshipable->getRelatedModelsFromGivenType(Institution::class);
        });

        return collect($relationships)->flatten(1);
    }
}
