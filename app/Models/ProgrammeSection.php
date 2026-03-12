<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Database\Factories\ProgrammeSectionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property array|string $title
 * @property int $duration
 * @property string|null $start_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ProgrammeBlock> $blocks
 * @property-read ProgrammeElement|null $pivot
 * @property-read Collection<int, ProgrammeDay> $programmeDays
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
    /** @use HasFactory<ProgrammeSectionFactory> */
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
