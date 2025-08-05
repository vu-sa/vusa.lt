<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $form_id
 * @property array<array-key, mixed> $label
 * @property array<array-key, mixed>|null $description
 * @property string $type
 * @property string|null $subtype
 * @property array<array-key, mixed>|null $options
 * @property bool $is_required
 * @property int $order
 * @property array<array-key, mixed>|null $default_value
 * @property array<array-key, mixed>|null $placeholder
 * @property bool $use_model_options
 * @property string|null $options_model
 * @property string|null $options_model_field
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FieldResponse> $fieldResponses
 * @property-read \App\Models\Form $form
 * @property-read mixed $translations
 * @method static \Database\Factories\FormFieldFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormField query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormField whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormField whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormField whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormField whereLocales(string $column, array $locales)
 * @mixin \Eloquent
 */
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
