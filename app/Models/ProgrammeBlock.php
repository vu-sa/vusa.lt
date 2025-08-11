<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $programme_section_id
 * @property array|string $title
 * @property array|string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProgrammePart> $parts
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\ProgrammeBlockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeBlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeBlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeBlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeBlock whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeBlock whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeBlock whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProgrammeBlock whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class ProgrammeBlock extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammeBlockFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'description'];

    public $translatable = ['title', 'description'];

    public function parts()
    {
        return $this->belongsToMany(ProgrammePart::class, 'programme_block_part');
    }
}
