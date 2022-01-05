<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyInstitutionType extends Model
{
    use HasFactory;

    protected $table = 'duties_institutions_types';

    public function dutyInstitution()
    {
        return $this->hasMany(DutyInstitution::class, 'type_id');
    }
}
