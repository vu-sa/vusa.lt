<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeSection extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammeSectionFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title'];

    public $translatable = ['title'];

    public function programmeDays()
    {
        return $this->morphToMany(ProgrammeDay::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }

    public function blocks()
    {
        return $this->hasMany(ProgrammeBlock::class);
    }
}
