<?php

namespace App\Models;

use App\Models\Traits\HasUnitRelation;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Model query()
 *
 * @mixin \Eloquent
 */
class Model extends EloquentModel
{
    use HasUnitRelation;
}
