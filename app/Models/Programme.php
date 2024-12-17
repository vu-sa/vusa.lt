<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammeFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'description'];

    public $translatable = ['title', 'description'];

    public function days()
    {
        return $this->hasMany(ProgrammeDay::class);
    }

    public function programmable()
    {
        return $this->morphTo();
    }
}
