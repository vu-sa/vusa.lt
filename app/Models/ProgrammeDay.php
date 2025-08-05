<?php

namespace App\Models;

use App\Models\Pivots\ProgrammeElement;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $programme_id
 * @property array|string $title
 * @property array|string|null $description
 * @property int $order
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProgrammeElement> $elements
 * @property-read ProgrammeElement|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammePart> $parts
 * @property-read \App\Models\Programme $programme
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammeSection> $sections
 * @property-read mixed $translations
 * @method static \Database\Factories\ProgrammeDayFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeDay whereLocales(string $column, array $locales)
 * @mixin \Eloquent
 */
class ProgrammeDay extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammeDayFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'date', 'start_time'];

    public $translatable = ['title', 'description'];

    protected $casts = [
        'start_time' => 'datetime',
    ];

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
