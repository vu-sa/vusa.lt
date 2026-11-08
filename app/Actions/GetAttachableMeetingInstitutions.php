<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
use App\Services\InstitutionRelationService;
use Illuminate\Database\Eloquent\Collection;

class GetAttachableMeetingInstitutions
{
    /** @return Collection<int, Institution> */
    public static function execute(User $user, Meeting $meeting): Collection
    {
        $ownIds = $user->authorization_duties()->pluck('institution_id')->filter()->unique();
        $relations = app(InstitutionRelationService::class);
        $relatedIds = $ownIds->flatMap(fn (string $id) => $relations->relatedTo($id)->pluck('institution_id'));

        return Institution::query()
            ->whereIn('id', $ownIds->merge($relatedIds)->unique())
            ->whereNotIn('id', $meeting->institutions()->pluck('institutions.id'))
            ->with('tenant:id,shortname')
            ->get();
    }
}
