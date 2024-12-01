<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    /** @use HasFactory<\Database\Factories\FormFieldFactory> */
    use HasFactory, HasTranslations;

    public $translatable = [
        'label',
        'description',
        'default_value',
        'placeholder',
    ];

    protected $fillable = [
        'id',
        'label',
        'description',
        'type',
        'subtype',
        'options',
        'is_required',
        'default_value',
        'placeholder',
        'order',
        'use_model_options',
        'options_model',
        'options_model_field',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'use_model_options' => 'boolean',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function fieldResponses()
    {
        return $this->hasMany(FieldResponse::class);
    }
}
