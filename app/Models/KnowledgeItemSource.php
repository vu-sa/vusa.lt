<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $knowledge_item_id
 * @property string $locale
 * @property int|null $document_id
 * @property string|null $url
 * @property Carbon|null $checked_at
 * @property int|null $check_status
 * @property int $check_failures
 * @property string|null $content_version
 * @property string|null $thumbnail_path
 * @property string|null $thumbnail_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Document|null $document
 * @property-read KnowledgeItem $item
 *
 * @method static \Database\Factories\KnowledgeItemSourceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSource query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['knowledge_item_id', 'locale', 'document_id', 'url'])]
class KnowledgeItemSource extends Model
{
    use HasFactory;

    public const int BROKEN_AFTER_FAILURES = 3;

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function isUrl(): bool
    {
        return $this->document_id === null && filled($this->url);
    }

    public function isBroken(): bool
    {
        return $this->isUrl() && $this->check_failures >= self::BROKEN_AFTER_FAILURES;
    }

    /** @return BelongsTo<KnowledgeItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(KnowledgeItem::class, 'knowledge_item_id');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
