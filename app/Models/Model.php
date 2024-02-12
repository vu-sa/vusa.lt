<?php

namespace App\Models;

use App\Models\Traits\HasUnitRelation;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    use HasUnitRelation;
}
