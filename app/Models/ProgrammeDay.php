<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Database\Factories\ProgrammeDayFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $programme_id
 * @property array|string $title
 * @property array|string|null $description
 * @property int $order
 * @property Carbon $start_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ProgrammeElement> $elements
 * @property-read ProgrammeElement|null $pivot
 * @property-read Collection<int, ProgrammePart> $parts
 * @property-read Programme $programme
 * @property-read Collection<int, ProgrammeSection> $sections
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\ProgrammeDayFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class ProgrammeDay extends Model
{
    /** @use HasFactory<ProgrammeDayFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'date', 'start_time'];

    public $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
        ];
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function elements()
    {
        return $this->hasMany(ProgrammeElement::class)->orderBy('order');
    }

    // Sections is a morphMany relationship
    public function sections()
    {
        return $this->morphedByMany(ProgrammeSection::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }

    public function parts()
    {
        return $this->morphedByMany(ProgrammePart::class, 'elementable', 'programme_day_elements')->using(ProgrammeElement::class);
    }
}
