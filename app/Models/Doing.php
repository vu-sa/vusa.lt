<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doing extends Model
{
    use HasFactory;

    protected $with = ['doing_type'];

    protected $guarded = [];

    public function question()
    {
        return $this->belongsTo(DutyInstitution::class);
    }

    public function doing_type()
    {
        return $this->belongsTo(DoingType::class);
    }
}
