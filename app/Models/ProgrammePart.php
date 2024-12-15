<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammePart extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammePartFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'description', 'start_time', 'duration'];

    public $translatable = ['title', 'description'];

    public function programmeDays()
    {
        return $this->morphToMany(ProgrammeDay::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }

    public function programmeBlocks()
    {
        return $this->belongsToMany(ProgrammeBlock::class, 'programme_block_part');
    }
}
