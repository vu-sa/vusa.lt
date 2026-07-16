<?php

namespace App\Models;

use App\Contracts\BelongsToProgramme;
use App\Models\Traits\HasTranslations;
use Database\Factories\ProgrammeBlockFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $programme_section_id
 * @property array|string $title
 * @property array|string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, ProgrammePart> $parts
 * @property-read ProgrammeSection|null $section
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
class ProgrammeBlock extends Model implements BelongsToProgramme
{
    /** @use HasFactory<ProgrammeBlockFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'description'];

    public $translatable = ['title', 'description'];

    public function parts()
    {
        return $this->belongsToMany(ProgrammePart::class, 'programme_block_part');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ProgrammeSection::class, 'programme_section_id');
    }

    /**
     * The programme this block belongs to, reached through its section. Drives
     * authorization — see {@see Programme::owningTraining()}.
     */
    public function owningProgramme(): ?Programme
    {
        return $this->section?->owningProgramme();
    }
}
