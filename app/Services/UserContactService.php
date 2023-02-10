<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;

class UserContactService {
    
    public static function getInstitutionBuilder(Padalinys $padalinys, Type $type) {
        $institutionBuilder = Institution::with(['duties.users'])
                ->orderBy('name')
                ->where([['padalinys_id', '=', $padalinys->id]])
                ->whereHas('types', function (Builder $query) use ($type) {
				    $query->whereIn('slug', $type->getDescendantsAndSelf()->pluck('slug'));
			    });

        return $institutionBuilder;
    }
}