<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    /** @use HasFactory<\Database\Factories\FormFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'description',
    ];

    public $translatable = [
        'name',
        'description',
        'path',
    ];

    public function formFields()
    {
        return $this->hasMany(FormField::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
