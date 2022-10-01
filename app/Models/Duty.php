<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DutyUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Duty extends Model
{
    use HasFactory;
    
    protected $table = 'duties';

    protected $with = ['types'];

    protected $casts = [
        'attributes' => 'array',
    ];

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'duties_users', 'duty_id', 'user_id')->using(DutyUser::class)->withPivot(['id', 'attributes'])->withTimestamps();
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function institution()
    {
        return $this->belongsTo(DutyInstitution::class, 'institution_id');
    }

    public function padalinys()
    {
        return $this->institution->padalinys;
    }
}
