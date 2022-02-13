<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaziningaiExamObserver extends Model
{

    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'saziningai_observers';

    public function flow()
    {
        return $this->belongsTo(SaziningaiExamFlow::class, 'flow_id');
    }

    public function exam()
    {
        return $this->belongsTo(SaziningaiExam::class, 'exam_uuid', 'uuid');
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }
}
