<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeBlock extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammeBlockFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'description'];

    public $translatable = ['title', 'description'];

    public function parts()
    {
        return $this->belongsToMany(ProgrammePart::class, 'programme_block_part');
    }
}
