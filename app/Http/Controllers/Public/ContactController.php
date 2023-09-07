<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ContactController extends PublicController
{
    public function contacts()
    {
        $this->getPadalinysLinks();

        $padaliniai = json_decode(base64_decode(request()->input('selectedPadaliniai'))) ??
            collect([Padalinys::query()->where('type', 'pagrindinis')->first()->id, $this->padalinys->id])->unique();

        $institutions = Institution::query()->with('padalinys', 'types:id,title,model_type,slug')
            ->whereHas('padalinys', fn ($query) =>
                $query->whereIn('id', $padaliniai)->select(['id', 'shortname', 'alias'])
            )->withCount('duties')->orderBy('name')->get()->makeHidden(['parent_id', 'created_at', 'updated_at', 'deleted_at', 'extra_attributes']);

        return Inertia::render('Public/Contacts/ContactsSearch', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    // shorten description and add ellipsis
                    'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                ];
            }),
            'selectedPadaliniai' => $padaliniai,
        ])->withViewData([
            'title' => 'Kontaktų paieška',
            'description' => 'VU SA kontaktai',
        ]);
    }

    public function institutionContacts($subdomain, $lang, Institution $institution)
    {
        $this->getPadalinysLinks();

        $contacts = $institution->load('duties.current_users.current_duties')->duties->sortBy(function ($duty) {
            return $duty->order;
        })->pluck('current_users')->flatten()->unique('id');

        // make eloquent collection from array
        $contacts = new Collection($contacts);

        return $this->showInstitution($institution, $contacts, $institution->name.' | Kontaktai');
    }

    public function institutionDutyTypeContacts($subdomain, $lang, Type $type)
    {
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

        return $this->showInstitution($institution, $contacts, $institution->name.' | '.ucfirst($type->slug));
    }

    public function studentRepresentatives()
    {
        $this->getPadalinysLinks();

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

    private function showInstitution(Institution $institution, Collection $contacts, string $title)
    {
        return Inertia::render('Public/Contacts/ContactInstitutionOrType', [
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
            'title' => $title,
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
