<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ContactController extends PublicController
{
    /**
     * Right now, contactsPage can return user to two different pages:
     * 1. Contacts page for specific institution
     * 2. Contacts page for student representatives
     *
     * @param  array  $rest
     * @return \Inertia\Response
     */
    public function contactsPage(Request $request, ...$rest)
    {
        // get last element of array, which is slug for this route
        $slug = end($rest);

        // check for the special page of studentu-atstovai (because they are in many institutions)

        if ($slug === 'studentu-atstovai') {
            $type = Type::query()->where('slug', '=', 'studentu-atstovu-organas')->first();
            $descendants = $type->getDescendantsAndSelf();

            $descendants->load(['institutions' => function ($query) {
                $query->with('duties.users', 'padalinys:id,alias')->where('padalinys_id', '=', $this->padalinys->id)->orderBy('name')->get(['id', 'name', 'alias', 'description']);
            }]);

            // remove descendants without institutions
            $descendants = $descendants->filter(function ($descendant) {
                return $descendant->institutions->count() > 0;
            })->values();

            return Inertia::render('Public/Contacts/ShowStudentReps', [
                'types' => $descendants,
            ])->withViewData([
                'title' => 'Studentų atstovai',
                'description' => "{$this->padalinys->shortname} studentų atstovai",
            ]);
        }

        // check for special cases, that are in one institution always

        if (in_array($slug, [null, 'koordinatoriai', 'kuratoriai'])) {
            $types = Type::query()->where('slug', '=', $slug)->get()->first()->getDescendantsAndSelf();

            if ($this->padalinys->type === 'pagrindinis') {
                $institution = Institution::where('alias', '=', 'centrinis-biuras')->first();
            } else {
                $institution = Institution::where('alias', '=', $this->padalinys->alias)->first();
            }

            $contacts = User::withWhereHas('duties', function ($query) use ($types, $institution) {
                $query->where('institution_id', '=', $institution->id)
                    ->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id'))
                    );
            })->get();

        // if not found, try to find institution
        } else {
            $institution = Institution::where('alias', '=', $slug)->first();

            if (is_null($institution)) {
                abort(404);
            }

            $contacts = User::withWhereHas('duties', function ($query) use ($slug) {
                $query->orderBy('order')->whereHas('institution', function ($query) use ($slug) {
                    $query->where('alias', '=', $slug);
                });
            })
            ->get();
        }

        // sort users by their duty smallest order
        $contacts = $contacts->sortBy(function ($contact) {
            return $contact->duties->min('order');
        })->values();

        return Inertia::render('Public/Contacts/ContactsShow', [
            'institution' => $institution,
            'contacts' => $contacts->map(function ($contact) use ($institution) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'duties' => $contact->duties->where('institution_id', '=', $institution->id),
                    'profile_photo_path' => $contact->profile_photo_path,
                    // 'profile_photo_path' => function () use ($contact) {
                    // 	if (substr($contact->profile_photo_path, 0, 4) == 'http') {
                    // 		return $contact->profile_photo_path;
                    // 	} else if (is_null($contact->profile_photo_path)) {
                    // 		return null;
                    // 	} else {
                    // 		return Storage::get(str_replace('uploads', 'public', $contact->profile_photo_path)) == null ? null : $contact->profile_photo_path;
                    // 	}
                    // },
                ];
            }),
        ])->withViewData([
            'title' => $this->padalinys->name.' kontaktai',
            // description html to plain text
            'description' => strip_tags($this->padalinys->description),
        ]);
    }

    /**
     * contactsCategory
     *
     * @param  array  $rest
     * @return \Inertia\Response
     */
    public function contactsCategory(Request $request, ...$rest)
    {
        $slug = end($rest);

        // Special case for 'padaliniai' alias, since it's a special category, fetched from 'padaliniai' table

        if ($slug === 'padaliniai') {
            $padaliniai = Padalinys::with('institutions')
                ->where('type', '=', 'padalinys')
                ->get();

            // pluck padalinys alias and get institutions wherein
            $institutions = Institution::whereIn('alias', $padaliniai->pluck('alias'))->orderBy('name')->get();
        } else {
            $institutions = Institution::whereHas('duties')
                // TODO: Čia reikia aiškesnės logikos
                ->when(
                    $slug,
                    // If /kontaktai/{} or /kontaktai/kategorija/{}
                    function ($query) {
                        return $query->where([['padalinys_id', '=', $this->padalinys->id], ['alias', 'not like', '%studentu-atstovai%']]);
                    },
                    // If there's an alias in the url
                    function ($query) {
                        return $query->where('alias', '=', request()->alias);
                    }
                )->get();
        }

        // check if institution array length is 1, then just return that one institution contacts.
        if ($institutions->count() == 1) {
            // redirect to that one institution page
            return redirect('kontaktai/'.$institutions->first()->alias);
        }

        return Inertia::render('Public/Contacts/ShowContactCategory', [
            'institutions' => $institutions,
        ])->withViewData([
            'title' => 'Kontaktai',
            'description' => 'VU SA kontaktai',
        ]);
    }

    public function contacts()
    {
        // get padalinys alias
        $alias = $this->padalinys->alias;

        return redirect()->route('contacts.alias', ['alias' => $alias, 'padalinys' => $alias === 'vusa' ? 'www' : $alias]);
    }
}
