<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaziningaiExam extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'saziningai_exams';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    public function flows()
    {
        return $this->hasMany(SaziningaiExamFlow::class, 'exam_uuid', 'uuid');
    }

    public function observers()
    {
        return $this->hasMany(SaziningaiExamObserver::class, 'exam_uuid', 'uuid');
    }
}
