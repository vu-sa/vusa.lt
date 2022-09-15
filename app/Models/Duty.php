<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DutyUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Duty extends Model
{
    use HasFactory;
    
    protected $table = 'duties';

    protected $with = ['type'];

    protected $casts = [
        'attributes' => 'array',
    ];

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'duties_users', 'duty_id', 'user_id')->using(DutyUser::class)->withPivot(['id', 'attributes'])->withTimestamps();
    }

    public function type()
    {
        return $this->belongsTo(DutyType::class, 'type_id');
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
