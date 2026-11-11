<?php

namespace App\Models;

use App\Actions\SyncKnowledgeLinks;
use App\Contracts\Commentable;
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $knowledge_section_id
 * @property-read KnowledgeSection|null $section
 * @property-read Collection<int, LearningAudience> $audiences
 * @property string $id
 * @property string|null $key
 * @property int|null $tenant_id
 * @property array|string|null $title
 * @property array|string|null $abbreviation
 * @property array|string|null $summary
 * @property array|string|null $description
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $reviewed_at
 * @property string|null $created_by
 * @property-read int $comments_count
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, KnowledgeItemSource> $sources
 * @property-read Collection<int, LearningProgress> $progress
 * @property-read Collection<int, KnowledgeItem> $outgoingLinks
 * @property-read Collection<int, KnowledgeItem> $incomingLinks
 * @property-read Collection<int, KnowledgeItemSubject> $subjects
 * @property-read Tenant|null $tenant
 * @property-read User|null $createdBy
 */
#[Fillable(['key', 'knowledge_section_id', 'tenant_id', 'title', 'abbreviation', 'summary', 'description', 'order', 'is_active', 'reviewed_at', 'created_by'])]
class KnowledgeItem extends Model implements Commentable
{
    use HasComments, HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    protected static function booted(): void
    {
        static::deleting(function (self $item): void {
            $item->audiences()->get()->each->delete();
            $item->progress()->delete();
        });
        static::saved(fn (self $item) => SyncKnowledgeLinks::execute($item));
    }

    public $translatable = ['title', 'abbreviation', 'summary', 'description'];

    /** @return list<string> */
    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    /** @return HasMany<KnowledgeItemSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(KnowledgeItemSource::class);
    }

    /** @return MorphMany<LearningProgress, $this> */
    public function progress(): MorphMany
    {
        return $this->morphMany(LearningProgress::class, 'progressable');
    }

    /** @return MorphMany<LearningAudience, $this> */
    public function audiences(): MorphMany
    {
        return $this->morphMany(LearningAudience::class, 'audienceable')->orderBy('order')->orderBy('id');
    }

    /** @return BelongsTo<KnowledgeSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSection::class, 'knowledge_section_id');
    }

    /** @return BelongsToMany<KnowledgeItem, $this> */
    public function outgoingLinks(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'knowledge_links', 'from_item_id', 'to_item_id');
    }

    /** @return BelongsToMany<KnowledgeItem, $this> */
    public function incomingLinks(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'knowledge_links', 'to_item_id', 'from_item_id');
    }

    /** @return HasMany<KnowledgeItemSubject, $this> */
    public function subjects(): HasMany
    {
        return $this->hasMany(KnowledgeItemSubject::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'reviewed_at' => 'datetime', 'order' => 'integer'];
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

    public function sourceFor(string $locale): ?KnowledgeItemSource
    {
        return $this->sources->firstWhere('locale', $locale) ?? $this->sources->firstWhere('locale', 'lt') ?? $this->sources->first();
    }
}
