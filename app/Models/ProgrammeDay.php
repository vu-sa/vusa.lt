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

    protected $fillable = ['title', 'date', 'start_time'];

    public $translatable = ['title', 'description'];

    protected $casts = [
        'start_time' => 'datetime',
    ];

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function elements()
    {
        return $this->hasMany(ProgrammeElement::class)->orderBy('order');
    }

    // Sections is a morphMany relationship
    public function sections()
    {
        return $this->morphedByMany(ProgrammeSection::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }

    public function parts()
    {
        return $this->morphedByMany(ProgrammePart::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }
}
