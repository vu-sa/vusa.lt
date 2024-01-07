<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContactController extends PublicController
{
    public function contacts()
    {
        $this->getPadalinysLinks();
        $this->shareOtherLangURL('contacts', $this->subdomain);

        $padaliniai = json_decode(base64_decode(request()->input('selectedPadaliniai'))) ??
            collect([Padalinys::query()->where('type', 'pagrindinis')->first()->id, $this->padalinys->id])->unique();

        $institutions = Institution::query()->with('padalinys', 'types:id,title,model_type,slug')
            ->whereHas('padalinys', fn ($query) => $query->whereIn('id', $padaliniai)->select(['id', 'shortname', 'alias'])
            )->withCount('duties')->orderBy('name')->get()->makeHidden(['parent_id', 'created_at', 'updated_at', 'deleted_at', 'extra_attributes']);

        return Inertia::render('Public/Contacts/ContactsSearch', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    // shorten description and add ellipsis
                    //'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                    //  TODO: better solution for displaying description or remove completely
                    'description' => '',
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
        Inertia::share('otherLangURL', route('contacts.institution', ['subdomain' => $this->subdomain, 'lang' => $this->getOtherLang(), 'institution' => $institution->id]));

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
        Inertia::share('otherLangURL', route('contacts.dutyType', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(), 'type' => $type->slug]));

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
        $this->shareOtherLangURL('contacts.studentRepresentatives', $this->subdomain);

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
                    'duties' => $contact->current_duties->where('institution_id', '=', $institution->id)->values(),
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

        Inertia::share('otherLangURL', route('contacts.category', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(), 'type' => $type->slug]));

        $institutions = $type->load(['institutions' => function ($query) {
            $query->orderBy('name')->with(['padalinys' => function ($query) {
                $query->where('type', 'padalinys');
            }]);
        }])->institutions;

        return Inertia::render('Public/Contacts/ShowContactCategory', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    // shorten description and add ellipsis
                    // 'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                    //  TODO: better solution for displaying description or remove completely
                    'description' => '',
                ];
            }),
            'type' => $type->unsetRelation('institutions'),
        ])->withViewData([
            'title' => 'Kontaktai: '.$type->title,
            'description' => 'VU SA kontaktai: '.$type->title,
        ]);
    }
}
