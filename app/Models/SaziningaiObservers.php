<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaziningaiObservers extends Model
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
        return $this->belongsTo(SaziningaiExamFlows::class, 'flow_id');
    }

    public function exam()
    {
        return $this->belongsTo(SaziningaiExams::class, 'exam_uuid', 'uuid');
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }
}
