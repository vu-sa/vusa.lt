<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property array|string $title
 * @property array|string|null $description
 * @property string|null $instructor
 * @property int $duration
 * @property string|null $start_time
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammeBlock> $programmeBlocks
 * @property-read ProgrammeElement|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammeDay> $programmeDays
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\ProgrammePartFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammePart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammePart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammePart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammePart whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammePart whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammePart whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammePart whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class ProgrammePart extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammePartFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'description', 'start_time', 'duration', 'instructor'];

    public $translatable = ['title', 'description'];

    public function programmeDays()
    {
        return $this->morphToMany(ProgrammeDay::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }

    public function programmeBlocks()
    {
        return $this->belongsToMany(ProgrammeBlock::class, 'programme_block_part');
    }
}
