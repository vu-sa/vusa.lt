<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaziningaiExams extends Model
{

    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'saziningai_exams';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
    ];

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    public function flows()
    {
        return $this->hasMany(SaziningaiExamsFlow::class, 'exam_uuid', 'uuid');
    }

    public function observers()
    {
        return $this->hasMany(SaziningaiObservers::class, 'exam_uuid', 'uuid');
    }
}
