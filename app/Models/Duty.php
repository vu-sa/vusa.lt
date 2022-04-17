<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Duty extends Model
{
    protected $table = 'duties';

    public function users()
    {
        return $this->belongsToMany(User::class, 'duties_users', 'duty_id', 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(DutyType::class, 'type_id');
    }

    public function institution()
    {
        return $this->belongsTo(DutyInstitution::class, 'institution_id');
    }
}
