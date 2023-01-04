<?php

namespace App\Http\Controllers\Admin;

use App\Models\Duty;
use App\Models\Institution;
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

        return Inertia::render('Admin/People/IndexDuty', [
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
        $institutions = $this->getInstitutionsForForm();
        
        return Inertia::render('Admin/People/CreateDuty', [
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'institutions' => $institutions,
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

        $duty->extra_attributes = $request->extra_attributes;
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
        $institutions = $this->getInstitutionsForForm();

        return Inertia::render('Admin/People/EditDuty', [
            'duty' => [
                ...$duty->load('institution')->toArray(),
                'types' => $duty->types->pluck('id'),
            ],
            'users' => $duty->users,
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'institutions' => $institutions
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
            $duty->update($request->only('name', 'description', 'email', 'extra_attributes'));

            $duty->institution()->disassociate();
            $duty->institution()->associate(Institution::find($request->institution['id']));
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

    private function getInstitutionsForForm(): Collection
    {
        return Institution::select('id', 'name', 'alias', 'padalinys_id')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
                $query->where('padalinys_id', auth()->user()->padalinys()->id);
        })->with('padalinys:id,shortname')->get();
    }
}
