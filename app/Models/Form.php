<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    /** @use HasFactory<\Database\Factories\FormFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'path',
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
