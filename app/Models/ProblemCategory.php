<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property array|string $name
 * @property string $slug
 * @property array|string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Problem> $problems
 * @property-read mixed $translations
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class ProblemCategory extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'slug', 'description'];

    public function problems(): BelongsToMany
    {
        return $this->belongsToMany(Problem::class);
    }
}
