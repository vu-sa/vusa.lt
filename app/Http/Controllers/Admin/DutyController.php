<?php

namespace App\Http\Controllers\Admin;

use App\Models\Duty;
use App\Models\DutyInstitution;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use App\Models\Type;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DutyController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Duty::class, 'duty');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // For search
        $title = $request->title;

        $duties = Duty::when(!is_null($title), function ($query) use ($title) {
            $query->where('name', 'like', "%{$title}%")->orWhere('email', 'like', "%{$title}%");
        })->when(!$request->user()->hasRole('Super Admin'), function ($query) {
            $query->whereHas('institution', function ($query) {
                $query->where('padalinys_id', auth()->user()->padalinys()->id);
            });
        })->with(['institution:id,name,short_name,padalinys_id','institution.padalinys:id,shortname'])->paginate(20);

        return Inertia::render('Admin/Contacts/IndexDuties', [
            'duties' => $duties,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $dutyInstitutions = $this->getDutyInstitutionsForForm();
        
        return Inertia::render('Admin/Contacts/CreateDuty', [
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'dutyInstitutions' => $dutyInstitutions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'institution' => 'required',
        ]);

        $duty = Duty::create([
            'name' => $request->name,
            'email' => $request->email,
            'description' => $request->description,
            'institution_id' => $request->institution['id'],
        ]);

        $duty->attributes = $request->all()['attributes'];
        $duty->save();

        $duty->types()->sync($request->type);

        return redirect()->route('duties.index')->with('success', 'Pareigybė sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function show(Duty $duty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function edit(Duty $duty)
    {
        $dutyInstitutions = $this->getDutyInstitutionsForForm();

        return Inertia::render('Admin/Contacts/EditDuty', [
            'duty' => [
                ...$duty->load('institution')->toArray(),
                'type' => $duty->types->first()?->id,
            ],
            'users' => $duty->users,
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'dutyInstitutions' => $dutyInstitutions
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Duty $duty)
    {
        $request->validate([
            'name' => 'required',
            'institution' => 'required',
        ]);

        DB::transaction(function () use ($request, $duty) {
            $duty->update($request->only('name', 'description', 'email', 'attributes'));

            $duty->institution()->disassociate();
            $duty->institution()->associate(DutyInstitution::find($request->institution['id']));
            $duty->save();

            $duty->types()->sync($request->types);
        });

        return back()->with('success', 'Pareigybė sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function destroy(Duty $duty)
    {
        $duty->delete();

        return redirect()->route('duties.index')->with('info', 'Pareigybė sėkmingai ištrinta!');
    }

    private function getDutyInstitutionsForForm(): Collection
    {
        return DutyInstitution::select('id', 'name', 'alias', 'padalinys_id')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
                $query->where('padalinys_id', auth()->user()->padalinys()->id);
        })->with('padalinys:id,shortname')->get();
    }
}
