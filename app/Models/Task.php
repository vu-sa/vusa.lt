<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Task extends Model
{
    use HasFactory, HasUlids, HasRelationships, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'timestamp'
    ];

    public function taskable()
    {
        return $this->morphTo();
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function padaliniai()
    {   
        return $this->hasManyDeepFromRelations($this->users(), (new User)->padaliniai());
    }
}
