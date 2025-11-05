<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Problem> $problems
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
