<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\Institution;
use App\Models\User;
use App\Services\RelationshipService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {

        // load user duty institutions
        // TODO: need to make it current duty institutions.
        $user = User::with('duties.institution.padalinys', 'duties.institution.users:users.id,users.name,profile_photo_path,phone')->with(['doings' => function (Builder $query) {
            $query->with('comments', 'tasks')->where('deleted_at', null)->orderBy('date', 'desc');
        }])->with('reservations')->find(auth()->user()->id);

        $duties = $user->duties;

        $institutions = $duties->pluck('institution')->flatten()->unique()->values();

        // convert to eloquent collection
        $institutions = new EloquentCollection($institutions);

        // load institutions with meetings where start_time is in the future
        // ! The filter() is needed, because some duties may have no institutions and they are null here
        $institutions = $institutions->filter()->load(['meetings' => function ($query) {
            $query->where('start_time', '>', now())->with('comments', 'tasks', 'files');
        }]);

        return Inertia::render('Admin/ShowDashboard', [
            'currentUser' => [
                ...$user->toArray(),
                'institutions' => $institutions->values(),
                'doings' => $user->doings->sortBy('date')->values(),
            ],
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
            'user' => $user->toFullArray(),
        ]);
    }

    public function updateUserSettings(Request $request)
    {
        $user = User::find(Auth::id());

        $user->update($request->all());

        return redirect()->back()->with('success', 'Nustatymai išsaugoti.');
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

    public function institutionGraph()
    {
        // return institutions with user count
        $institutions = Institution::withCount('users')->get();

        return Inertia::render('Admin/ShowInstitutionGraph', [
            'institutions' => $institutions,
            'institutionRelationships' => RelationshipService::getAllRelatedInstitutions(),
        ]);
    }

    public function workspace(Request $request)
    {
        return Inertia::render('Admin/ShowWorkspace',
            [
                'institution' => fn () => $this->getInstitutionForWorkspace($request),
                // get all institutions where has relationship with auth user
                'userInstitutions' => Institution::whereHas('users', function ($query) {
                    $query->where('users.id', auth()->user()->id);
                })->with('users')->withCount('meetings')->get(),
            ]
        );
    }

    public function sendFeedback(Request $request)
    {
        $request->validate([
            'feedback' => 'required|string',
            'anonymous' => 'boolean',
        ]);

        // just send simple email to it@vusa.lt with feedback, conditional user name and with in a queue
        Mail::to('it@vusa.lt')->queue(new \App\Mail\FeedbackMail($request->input('feedback'), $request->input('anonymous') ? null : auth()->user()));

        return redirect()->back()->with('success', 'Ačiū už atsiliepimą!');
    }

    protected function getInstitutionForWorkspace(Request $request)
    {
        $institution = null;

        // check if institution has id
        if (! is_null($request->input('institution_id'))) {
            $institution = Institution::with('meetings.comments', 'meetings.tasks', 'meetings.files', 'users')->find($request->input('institution_id'));
        }

        return $institution;
    }
}
