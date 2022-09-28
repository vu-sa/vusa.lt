<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doing extends Model
{
    use HasFactory;

    protected $with = ['type'];

    public function question()
    {
        return $this->belongsTo(DutyInstitution::class);
    }

    public function type()
    {
        return $this->belongsTo(DoingType::class);
    }
}
