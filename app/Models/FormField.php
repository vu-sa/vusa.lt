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
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
