<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Type;
use App\Models\User;
use App\Services\UserContactService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ContactController extends PublicController
{
    public function contactsPage(Request $request, ...$rest)
	{
		// get last element of array, which is slug for this route
        $slug = end($rest);
        
        if ($slug === 'studentu-atstovai') {
			$type = Type::where('slug', '=', 'studentu-atstovu-organas')->first();

			return Inertia::render('Public/Contacts/StudentRepresentatives', [
				'institutions' => UserContactService::getInstitutionBuilder($this->padalinys, $type)
                    ->get(['id', 'name', 'alias', 'description'])
			])->withViewData([
				'title' => 'Studentų atstovai',
				'description' => "{$this->padalinys->shortname} studentų atstovai",
			]);
		}

		if (in_array($slug, [null, 'koordinatoriai', 'kuratoriai'])) {

			$types = Type::where('slug', '=', $slug)->get()->first()->getDescendantsAndSelf();

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

		} else {
			
			$institution = Institution::where('alias', '=', $this->alias)->first();

			if (is_null($institution)) {
				abort(404);
			}
			
			$contacts = User::withWhereHas('duties', function ($query) {
				$query->whereHas('institution', function ($query) {
					$query->where('alias', '=', $this->alias);
				});
			})->get();
		}

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
			})
		])->withViewData([
			'title' => $this->padalinys->name . ' kontaktai',
			// description html to plain text
			'description' => strip_tags($this->padalinys->description),
		]);
	}
}