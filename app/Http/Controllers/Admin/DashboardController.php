<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\Institution;
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

        $institutions = $duties->pluck('institution')->flatten()->unique();

        // convert to eloquent collection
        $institutions = new EloquentCollection($institutions);

        $institutions->load('users:users.id,users.name,profile_photo_path');

        return Inertia::render('Admin/ShowDashboard', [
            'institutions' => $institutions,
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
