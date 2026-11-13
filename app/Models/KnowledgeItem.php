<?php

namespace App\Models;

use App\Actions\SyncKnowledgeLinks;
use App\Contracts\Commentable;
use App\Enums\KnowledgeItemKind;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
 * @property string $id
 * @property string|null $key
 * @property KnowledgeItemKind $kind
 * @property string|null $knowledge_section_id
 * @property int|null $tenant_id
 * @property array|string $title
 * @property array|string|null $abbreviation
 * @property array|string|null $summary
 * @property array|string|null $description
 * @property int $order
 * @property bool $is_active
 * @property bool $counts_in_progress
 * @property Carbon|null $reviewed_at
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Collection<int, LearningAudience> $audiences
 * @property-read Collection<int, Comment> $comments
 * @property-read User|null $createdBy
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, KnowledgeItem> $incomingLinks
 * @property-read Collection<int, KnowledgeItem> $outgoingLinks
 * @property-read Collection<int, LearningProgress> $progress
 * @property-read Collection<int, Comment> $rootComments
 * @property-read KnowledgeSection|null $section
 * @property-read Collection<int, KnowledgeItemSource> $sources
 * @property-read Collection<int, KnowledgeItemSubject> $subjects
 * @property-read Tenant|null $tenant
 * @property-read mixed $translations
 *
 * @method static Builder<static>|KnowledgeItem active()
 * @method static \Database\Factories\KnowledgeItemFactory factory($count = null, $state = [])
 * @method static Builder<static>|KnowledgeItem newModelQuery()
 * @method static Builder<static>|KnowledgeItem newQuery()
 * @method static Builder<static>|KnowledgeItem ordered()
 * @method static Builder<static>|KnowledgeItem query()
 * @method static Builder<static>|KnowledgeItem terms()
 * @method static Builder<static>|KnowledgeItem whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|KnowledgeItem whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|KnowledgeItem whereLocale(string $column, string $locale)
 * @method static Builder<static>|KnowledgeItem whereLocales(string $column, array $locales)
 * @method static Builder<static>|KnowledgeItem whereTranslationLike(array|string $columns, string $search, ?array $locales = null)
 *
 * @mixin \Eloquent
 */
#[Fillable(['key', 'kind', 'knowledge_section_id', 'tenant_id', 'title', 'abbreviation', 'summary', 'description', 'order', 'is_active', 'counts_in_progress', 'reviewed_at', 'created_by'])]
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

    /** Match the column defaults, so a freshly created article reads the same as a reloaded one. */
    #[\Override]
    protected $attributes = ['kind' => 'term', 'counts_in_progress' => true];

    /** @return list<string> */
    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    /** @return list<string> */
    protected function inlineHtmlTranslations(): array
    {
        return ['summary'];
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
        return ['kind' => KnowledgeItemKind::class, 'is_active' => 'boolean', 'counts_in_progress' => 'boolean', 'reviewed_at' => 'datetime', 'order' => 'integer'];
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function terms(Builder $query): void
    {
        $query->where('kind', KnowledgeItemKind::Term);
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('order')->orderBy('id');
    }

    public function sourceFor(string $locale): ?KnowledgeItemSource
    {
        return $this->sources->firstWhere('locale', $locale) ?? $this->sources->firstWhere('locale', 'lt') ?? $this->sources->first();
    }
}
