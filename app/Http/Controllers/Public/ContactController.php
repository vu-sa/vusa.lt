<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Institution;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;

class ContactController extends PublicController
{
    public function institutionContacts($subdomain, $lang, Institution $institution)
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        $contacts = $institution->load('duties.current_users.current_duties')->duties->sortBy(function ($duty) {
            return $duty->order;
        })->pluck('current_users')->flatten()->unique('id');

        // make eloquent collection from array
        $contacts = new Collection($contacts);

        return $this->showInstitution($institution, $contacts);
    }

    public function institutionDutyTypeContacts($subdomain, $lang, Type $type)
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        $types = $type->getDescendantsAndSelf();

        if ($this->padalinys->type === 'pagrindinis') {
            $institution = Institution::where('alias', '=', 'centrinis-biuras')->first();
        } else {
            $institution = Institution::where('alias', '=', $this->padalinys->alias)->firstOrFail();
        }

        // load duties whereHas types
        $contacts = $institution->load(['duties' => function ($query) use ($types) {
            $query->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id')))->with('current_users.current_duties');
        }])->duties->sortBy(function ($duty) {
            return $duty->order;
        })->pluck('current_users')->flatten()->unique('id');

        // make eloquent collection from array
        $contacts = new Collection($contacts);

        return $this->showInstitution($institution, $contacts);
    }

    public function studentRepresentatives($subdomain, $lang)
    {
        $type = Type::query()->where('slug', '=', 'studentu-atstovu-organas')->first();
        $descendants = $type->getDescendantsAndSelf();

        $descendants->load(['institutions' => function ($query) {
            $query
                ->with('duties.current_users:id,name,email,phone,profile_photo_path')
                ->with('padalinys:id,alias')
                ->where('padalinys_id', '=', $this->padalinys->id)
                ->orderBy('name')
                ->get(['id', 'name', 'alias', 'description']);
        }]);

        // remove descendants without institutions
        $descendants = $descendants->filter(function ($descendant) {
            return $descendant->institutions->count() > 0;
        })->values();

        return Inertia::render('Public/Contacts/ShowStudentReps', [
            'types' => $descendants,
        ])->withViewData([
            'title' => 'Studentų atstovai | '.$this->padalinys->shortname,
            'description' => "{$this->padalinys->shortname} studentų atstovai",
        ]);
    }

    private function showInstitution(Institution $institution, Collection $contacts)
    {
        return Inertia::render('Public/Contacts/ContactsShow', [
            'institution' => $institution,
            'contacts' => $contacts->map(function ($contact) use ($institution) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'duties' => $contact->current_duties->where('institution_id', '=', $institution->id),
                    'profile_photo_path' => $contact->profile_photo_path,
                ];
            }),
        ])->withViewData([
            'title' => $institution->name.' | Kontaktai',
            'description' => strip_tags($institution->description),
        ]);
    }

    /**
     * contactsCategory
     *
     * @param  array  $rest
     * @return \Inertia\Response
     */
    public function institutionCategory($subdomain, $lang, Type $type)
    {
        $this->getBanners();
        $this->getPadalinysLinks();

        $institutions = $type->load(['institutions' => function ($query) {
            $query->orderBy('name')->with(['padalinys' => function ($query) {
                $query->where('type', 'padalinys');
            }]);
        }])->institutions;

        return Inertia::render('Public/Contacts/ShowContactCategory', [
            'institutions' => $institutions,
        ])->withViewData([
            'title' => 'Kontaktai',
            'description' => 'VU SA kontaktai',
        ]);
    }
}
