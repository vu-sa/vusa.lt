<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaziningaiExamFlow extends Model
{
    use HasFactory;

    protected $table = 'saziningai_exam_flows';

    public function exam()
    {
        return $this->belongsTo(SaziningaiExam::class, 'exam_uuid', 'uuid');
    }

    public function observers()
    {
        return $this->hasMany(SaziningaiExamObserver::class, 'flow');
    }
}
