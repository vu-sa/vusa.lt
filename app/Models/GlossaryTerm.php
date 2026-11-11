<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $id
 * @property array|string|null $term
 * @property array|string|null $abbreviation
 * @property array|string|null $definition
 * @property-read Collection<int, LearningStep> $steps
 */
#[Fillable(['term', 'abbreviation', 'definition'])]
class GlossaryTerm extends Model
{
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['term', 'abbreviation', 'definition'];

    /** @return BelongsToMany<LearningStep, $this> */
    public function steps(): BelongsToMany
    {
        return $this->belongsToMany(LearningStep::class);
    }
}
