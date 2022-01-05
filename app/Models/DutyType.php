<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyType extends Model
{
    use HasFactory;

    protected $table = 'duties_types';

    public function duties()
    {
        return $this->hasMany(Duty::class, 'type_id');
    }
}
