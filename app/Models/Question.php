<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $with = ['question_group'];

    public function institution()
    {
        return $this->belongsTo(DutyInstitution::class);
    }

    public function doings()
    {
        return $this->hasMany(Doing::class);
    }

    public function question_group()
    {
        return $this->belongsTo(QuestionGroup::class);
    }
}
