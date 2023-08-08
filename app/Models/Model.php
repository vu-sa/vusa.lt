<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    // * The problem is that some models have different method names for getting the unit relation
    // * This returns the method name for the unit relation
    public function whichUnitRelation()
    {

        // check for padalinys relation
        if (method_exists($this, 'padalinys')) {
            return 'padalinys';
        }

        // check for padaliniai relation
        if (method_exists($this, 'padaliniai')) {
            return 'padaliniai';
        }

        // throw exception if no unit relation found
        throw new \Exception('No unit relation found');
    }
}
