<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['urlable_type', 'urlable_id', 'locale', 'url'])]
class PublicUrl extends Model
{
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function urlable(): MorphTo
    {
        return $this->morphTo();
    }
}
