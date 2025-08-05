<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property array|string $title
 * @property array|string|null $description
 * @property string $start_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammeDay> $days
 * @property-read Model|\Eloquent $programmable
 * @property-read mixed $translations
 * @method static \Database\Factories\ProgrammeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereLocales(string $column, array $locales)
 * @mixin \Eloquent
 */
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
