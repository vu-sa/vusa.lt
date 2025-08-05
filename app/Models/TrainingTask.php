<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;

/**
 * @property int $id
 * @property string $training_id
 * @property array|string $name
 * @property array|string|null $description
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Training $training
 * @property-read mixed $translations
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTask whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTask whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTask whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTask whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class TrainingTask extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public $translatable = ['name', 'description'];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
