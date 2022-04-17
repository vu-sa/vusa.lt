<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyInstitution extends Model
{
    use HasFactory;

    protected $table = 'duties_institutions';

    public function duties()
    {
        return $this->hasMany(Duty::class, 'institution_id');
    }

    public function type()
    {
        return $this->belongsTo(DutyInstitutionType::class, 'type_id');
    }
}
