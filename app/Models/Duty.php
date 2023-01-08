<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Dutiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Duty extends Model implements AuthorizableContract
{
    use HasFactory, Authorizable, HasRoles, LogsActivity, HasUlids, SoftDeletes;
    
    protected $casts = [
        'extra_attributes' => 'array',
    ];

    protected $guard_name = 'web';

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function dutiables()
    {
        return $this->hasMany(Dutiable::class);
    }

    public function users()
    {
        return $this->morphedByMany(User::class, 'dutiable')->using(Dutiable::class)->withPivot(['extra_attributes'])->withTimestamps();
    }

    public function contacts()
    {
        return $this->morphedByMany(Contact::class, 'dutiable')->using(Dutiable::class)->withPivot(['extra_attributes'])->withTimestamps();
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function padalinys()
    {
        return $this->institution->padalinys;
    }
}
