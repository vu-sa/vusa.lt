<?php

namespace App\Models;

use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SharepointFile extends Model
{
    use HasFactory, HasUuids, HasComments;
    
    public $timestamps = false;

    protected $guarded = [];

    public function fileable()
    {
        return $this->morphTo();
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
