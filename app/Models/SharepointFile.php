<?php

namespace App\Models;

use App\Models\Pivots\SharepointFileable;
use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharepointFile extends Model
{
    use HasComments, HasFactory, HasUuids;

    public $timestamps = false;

    protected $guarded = [];

    public function fileables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SharepointFileable::class, 'sharepoint_file_id', 'id');
    }

    public function types()
    {
        return $this->morphedByMany(Type::class, 'fileable');
    }

    public function institutions()
    {
        return $this->morphedByMany(Institution::class, 'fileable');
    }

    public function meetings()
    {
        return $this->morphedByMany(Meeting::class, 'fileable');
    }
}
