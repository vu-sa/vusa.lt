<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeDay extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammeDayFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'date'];

    public $translatable = ['title'];

    protected $casts = [
        'start_time' => 'datetime',
    ];

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function elements()
    {
        return $this->hasMany(ProgrammeElement::class);
    }
}
