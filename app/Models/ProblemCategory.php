<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Problem> $problems
 *
 * @method static \Database\Factories\ProblemCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProblemCategory query()
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
