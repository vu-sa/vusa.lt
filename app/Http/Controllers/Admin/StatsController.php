<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Padalinys;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function representativesInPadalinys()
    {
        // get all padaliniai, get institutions->duties where duty type is studentu atstovas and count how many users are in those padaliniai

        // get all padaliniai
        $padaliniai = Padalinys::with(['institutions' => function ($query) {
            $query->select('id', 'padalinys_id')->with(['duties' => function ($query) {
                $query->select('id', 'institution_id')->whereHas('types', function ($query) {
                    $query->where('slug', 'studentu-atstovai');
                })->with('users');
            }]);
        }])->orderBy('shortname')->get();

        $padaliniai->map(function ($padalinys) {
            $padalinys->institutions->map(function ($institution) {
                $institution->duties->map(function ($duty) {
                    $duty->users;
                });
            });
        });

        // flatten users to padalinys
        $padaliniai->each(function ($padalinys) {
            $padalinys->users = new Collection($padalinys->institutions->pluck('duties')->flatten()->pluck('users')->flatten()->unique('id')->values());

            $padalinys->users->makeVisible(['last_action']);
        });

        // calculate stats for each padalinys

        return Inertia::render('Admin/Stats/RepresentativesInPadalinys', [
            'padaliniai' => $padaliniai->toArray(),
        ]);
    }
}
