<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property array|string $title
 * @property int $duration
 * @property string|null $start_time
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammeBlock> $blocks
 * @property-read ProgrammeElement|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammeDay> $programmeDays
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\ProgrammeSectionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeSection whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeSection whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeSection whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeSection whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class ProgrammeSection extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammeSectionFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title'];

    public $translatable = ['title'];

    public function programmeDays()
    {
        return $this->morphToMany(ProgrammeDay::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }

    public function blocks()
    {
        return $this->hasMany(ProgrammeBlock::class);
    }
}
