<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;

class TrainingTask extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public $translatable = ['name', 'description'];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
