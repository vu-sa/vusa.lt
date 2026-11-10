<?php

namespace App\Models;

use App\Contracts\Commentable;
use App\Enums\LearningStepKind;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $learning_section_id
 * @property-read LearningSection|null $section
 * @property-read Collection<int, LearningStepAudience> $audiences
 * @property string $id
 * @property string|null $key
 * @property bool $is_system
 * @property LearningStepKind $kind
 * @property array|string|null $title
 * @property array|string|null $description
 * @property bool $per_term
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $reviewed_at
 * @property string|null $created_by
 * @property-read int $comments_count
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, LearningStepSource> $sources
 * @property-read Collection<int, LearningStepProgress> $progress
 * @property-read Collection<int, GlossaryTerm> $glossaryTerms
 * @property-read User|null $createdBy
 */
#[Fillable(['key', 'is_system', 'kind', 'learning_section_id', 'title', 'description', 'per_term', 'order', 'is_active', 'reviewed_at', 'created_by'])]
class LearningStep extends Model implements Commentable
{
    use HasComments, HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['title', 'description'];

    /** @return HasMany<LearningStepSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(LearningStepSource::class);
    }

    /** @return HasMany<LearningStepProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(LearningStepProgress::class);
    }

    /** @return HasMany<LearningStepAudience, $this> */
    public function audiences(): HasMany
    {
        return $this->hasMany(LearningStepAudience::class)->orderBy('order')->orderBy('id');
    }

    /** @return BelongsTo<LearningSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(LearningSection::class, 'learning_section_id');
    }

    /** @return BelongsToMany<GlossaryTerm, $this> */
    public function glossaryTerms(): BelongsToMany
    {
        return $this->belongsToMany(GlossaryTerm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return ['kind' => LearningStepKind::class, 'is_system' => 'boolean', 'per_term' => 'boolean', 'is_active' => 'boolean', 'reviewed_at' => 'datetime', 'order' => 'integer'];
    }

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order')->orderBy('id');
    }

    public function sourceFor(string $locale): ?LearningStepSource
    {
        return $this->sources->firstWhere('locale', $locale) ?? $this->sources->firstWhere('locale', 'lt') ?? $this->sources->first();
    }
}
