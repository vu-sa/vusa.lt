<?php

namespace App\Services;

use App\Models\User;
use App\Policies\Traits\UseUserDutiesForAuthorization;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ModelIndexginator {

    use UseUserDutiesForAuthorization;
    
    // check argument to be class of model
    public function execute(string $modelClass, string | null $search, string $searchable): LengthAwarePaginator
    {
        if (!class_exists($modelClass)) {
            // return exception that the class doesn't exist
            return new Exception('Class ' . $modelClass . ' does not exist');
        }
        
        $currentUser = User::find((Auth::id()));
        $lowercasedModel = Str::lcfirst(class_basename($modelClass));
        $pluralModel = Str::plural($lowercasedModel);

        // first need to check if has permission to view all models

        // TODO: get only needed data for index
        if ($this->forUser($currentUser)->check($pluralModel . '.read.*')) {
            return $modelClass::with('padalinys')->search($search)->paginate(20);
        } 

        if ($this->forUser($currentUser)->check($pluralModel . '.read.padalinys'))
        {
            $models = $modelClass::whereHas('padalinys', function (Builder $query) use ($currentUser) {
                $query->whereIn('id', $this->getPadaliniai()->pluck('id'));
            })->where($searchable, 'like', "%{$search}%")->paginate(20);
            
            return $models;
        }

        return new LengthAwarePaginator(new Collection(), 0, 20);
    }
}